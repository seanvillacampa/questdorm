<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Models\AuditLog;
use App\Notifications\TenantWelcome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::with(['user', 'contracts.room']);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $searchPattern = '%'.$search.'%';
            
            $query->whereHas('user', function($userQuery) use ($searchPattern) {
                $userQuery->where(function($q) use ($searchPattern) {
                    $q->whereRaw('LOWER(first_name) LIKE ?', [$searchPattern])
                      ->orWhereRaw('LOWER(middle_name) LIKE ?', [$searchPattern])
                      ->orWhereRaw('LOWER(last_name) LIKE ?', [$searchPattern])
                      ->orWhereRaw('LOWER(CONCAT(first_name, " ", COALESCE(middle_name, ""), " ", last_name)) LIKE ?', [$searchPattern]);
                });
            })
            ->orWhereHas('contracts.room', function($roomQuery) use ($searchPattern) {
                $roomQuery->whereRaw('LOWER(room_number) LIKE ?', [$searchPattern]);
            });
        }

        $tenants = $query->orderBy('id')->paginate(10)->withQueryString();

        return view('tenants.index', compact('tenants'));
    }

    public function create()
    {
        return view('tenants.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'      => 'required|string|max:255',
            'middle_name'     => 'nullable|string|max:255',
            'last_name'       => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email',
            'phone'           => 'nullable|string|max:30',
            'phone_country_code' => 'nullable|string|max:5',
            'birthdate'       => 'nullable|date|before:' . now()->subYears(18)->format('Y-m-d'),
            'id_type'         => 'nullable|in:National ID,Passport,Student ID,Driver\'s License,PRC ID,UMID',
            'id_number'       => 'nullable|string|max:50',
            'emergency_name'  => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:30',
            'emergency_phone_country_code' => 'nullable|string|max:5',
        ], [
            'birthdate.before' => 'Tenant must be at least 18 years old.',
        ]);

        // Check uniqueness of first_name + middle_name + last_name combination
        $nameQuery = User::where('first_name', $data['first_name'])
            ->where('last_name', $data['last_name']);
        
        // Handle middle name comparison (both null or both equal)
        if (empty($data['middle_name'])) {
            $nameQuery->whereNull('middle_name');
        } else {
            $nameQuery->where('middle_name', $data['middle_name']);
        }
        
        $nameExists = $nameQuery->exists();

        if ($nameExists) {
            return back()->withErrors([
                'first_name' => 'A tenant with this exact name already exists.'
            ])->withInput();
        }

        // Create user + tenant inside transaction
        try {
            $user = DB::transaction(function () use ($data) {
                $user = User::create([
                    'first_name'         => $data['first_name'],
                    'middle_name'        => $data['middle_name'] ?: null,
                    'last_name'          => $data['last_name'],
                    'email'              => $data['email'],
                    'phone'              => $data['phone'] ?? null,
                    'phone_country_code' => $data['phone_country_code'] ?? '+63',
                    'password'           => Hash::make(\Illuminate\Support\Str::random(16)),
                    'is_active'          => true,
                ]);
                $user->assignRole('tenant');

                $tenant = Tenant::create([
                    'user_id'         => $user->id,
                    'birthdate'       => $data['birthdate'] ?? null,
                    'id_type'         => $data['id_type'] ?? null,
                    'id_number'       => $data['id_number'] ?? null,
                    'emergency_name'  => $data['emergency_name'] ?? null,
                    'emergency_phone' => $data['emergency_phone'] ?? null,
                ]);

                AuditLog::record('Created', 'Tenant', $tenant->id,
                    "New tenant {$user->name} ({$user->email})");

                return $user;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Catch duplicate email or NOT NULL constraint violations gracefully
            if ($e->getCode() === '23000') {
                if (str_contains($e->getMessage(), 'users_email_unique') || str_contains($e->getMessage(), 'Duplicate entry')) {
                    return back()->withErrors(['email' => 'This email address is already registered.'])->withInput();
                }
                if (str_contains($e->getMessage(), 'middle_name') && str_contains($e->getMessage(), 'cannot be null')) {
                    return back()->withErrors(['middle_name' => 'Middle name is required by the database. Please enter N/A if not applicable.'])->withInput();
                }
            }
            throw $e; // re-throw unexpected DB errors
        }

        // Send activation email OUTSIDE the transaction so a mail failure
        // doesn't roll back the user record. Log any failure clearly.
        $emailSent = false;
        $errorMessage = null;
        try {
            $token = Password::broker()->createToken($user);
            $user->notify(new TenantWelcome($token));
            $emailSent = true;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error('TenantWelcome notification failed', [
                'tenant_email' => $user->email,
                'error'        => $errorMessage,
                'trace'        => $e->getTraceAsString(),
            ]);
        }

        if ($emailSent) {
            $message = "Tenant {$user->name} registered. Activation email sent to {$user->email}.";
        } else {
            $message = "Tenant {$user->name} registered. ⚠️ Activation email could not be sent.";
            if ($errorMessage) {
                $message .= " Error: " . $errorMessage;
            }
        }

        return redirect()->route('tenants.index')->with('success', $message);
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['user', 'contracts.room', 'contracts.invoices']);
        return view('tenants.show', compact('tenant'));
    }

    public function destroy(Tenant $tenant)
    {
        // Check if tenant is currently active in any contract (left_on is null)
        $hasActiveContract = DB::table('contract_tenants')
            ->where('tenant_id', $tenant->id)
            ->whereNull('left_on')
            ->exists();
        
        if ($hasActiveContract) {
            return redirect()->route('tenants.show', $tenant)
                ->with('error', 'Cannot delete tenant who is currently in an active contract. Remove them from the contract first.');
        }

        DB::beginTransaction();
        try {
            $tenantName = $tenant->user->name;
            $tenantEmail = $tenant->user->email;
            $userId = $tenant->user_id;

            // Delete tenant record (this will cascade delete tenant_payments, tenant_deposits via foreign keys)
            $tenant->delete();

            // Delete the user account
            User::where('id', $userId)->delete();

            AuditLog::record('Deleted', 'Tenant', $tenant->id,
                "Deleted tenant {$tenantName} ({$tenantEmail}) and their user account");

            DB::commit();

            return redirect()->route('tenants.index')
                ->with('success', "Tenant {$tenantName} and their account have been permanently deleted.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete tenant', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->route('tenants.show', $tenant)
                ->with('error', 'Failed to delete tenant: ' . $e->getMessage());
        }
    }
}
