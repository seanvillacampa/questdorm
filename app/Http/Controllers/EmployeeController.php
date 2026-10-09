<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class EmployeeController extends Controller
{
    public function index()
    {
        $staff = User::role(['owner','employee'])
            ->orderByDesc('created_at')
            ->get();

        return view('employees.index', compact('staff'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:30',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:owner,employee',
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'password'  => Hash::make($data['password']),
            'is_active' => true,
        ]);
        $user->assignRole($data['role']);

        AuditLog::record('Created', 'User', $user->id,
            "Employee {$user->name} ({$data['role']}) added");

        event(new Registered($user));

        return redirect()->route('employees.index')
            ->with('success', "{$user->name} added as {$data['role']}.");
    }

    public function toggle(User $user)
    {
        abort_if($user->hasRole('owner') && User::role('owner')->count() <= 1,
            422, 'Cannot disable the only owner account.');

        $user->update(['is_active' => ! $user->is_active]);

        $action = $user->is_active ? 'Enabled' : 'Disabled';
        AuditLog::record($action, 'User', $user->id,
            "Account {$action}: {$user->name}");

        return back()->with('success', "{$user->name} account {$action}.");
    }
}
