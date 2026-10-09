<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Self-registration for OWNER and EMPLOYEE accounts only.
 *
 * Tenants are never created here. A tenant account is created by staff when
 * a lease is signed (a separate, not-yet-built TenantController), and the
 * tenant activates it from an emailed link rather than filling this form.
 */
class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            // Combine names for the 'name' field
            $nameParts = array_filter([
                $data['first_name'],
                $data['middle_name'] ?? null,
                $data['last_name']
            ]);
            $fullName = implode(' ', $nameParts);
            
            $user = User::create([
                'name' => $fullName,
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'phone_country_code' => $data['phone_country_code'] ?? '+63',
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            // The 'owner' / 'employee' / 'tenant' role rows already exist —
            // they are seeded by database/dormitory_schema.sql. This just
            // attaches the pivot row in model_has_roles.
            $user->assignRole($data['role']);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
