<x-layouts.app title="Employees & Roles — Quest Building">

    <x-page-header title="Employees &amp; Roles" badge="Owner only"
        subtitle="Manage staff accounts and what each role can do">
        <x-slot:actions>
            <a href="{{ route('employees.create') }}"
               class="px-3.5 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                + Add employee
            </a>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-[1fr_320px] gap-4 items-start">
        {{-- Staff table --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-left">Role</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Last login</th>
                        <th class="px-4 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($staff as $u)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-semibold select-none">
                                        {{ strtoupper(substr($u->name,0,1)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $u->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $u->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $u->hasRole('owner') ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ ucfirst($u->primaryRole()) }}
                                </span>
                            </td>
                            <td class="px-4 py-3"><x-status-badge :status="$u->is_active ? 'active' : 'disabled'" /></td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                {{ $u->last_login_at ? $u->last_login_at->format('M d, Y g:i A') : 'Never' }}
                            </td>
                            <td class="px-4 py-3">
                                @if(!$u->hasRole('owner'))
                                    <form method="POST" action="{{ route('employees.toggle', $u) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="text-xs font-medium {{ $u->is_active ? 'text-red-500 hover:text-red-700' : 'text-green-600 hover:text-green-800' }}">
                                            {{ $u->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Permissions matrix --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Permissions</h2>
            @php
                $perms = [
                    'Manage rooms & tenants'  => [true, true],
                    'Enter meter readings'    => [true, true],
                    'Record cash payments'    => [true, true],
                    'Handle repairs'          => [true, true],
                    'Send reminders'          => [true, true],
                    'View reports'            => [true, false],
                    'Change rates & settings' => [true, false],
                    'Manage staff & roles'    => [true, false],
                    'Void invoices / records' => [true, false],
                    'View audit logs'         => [true, false],
                ];
            @endphp
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-gray-400 font-medium">
                        <th class="text-left pb-2"></th>
                        <th class="pb-2 text-center">Owner</th>
                        <th class="pb-2 text-center">Employee</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($perms as $label => [$owner, $employee])
                        <tr>
                            <td class="py-1.5 text-gray-600">{{ $label }}</td>
                            <td class="text-center">@if($owner)<span class="text-green-500">Yes</span>@else<span class="text-gray-300">—</span>@endif</td>
                            <td class="text-center">@if($employee)<span class="text-green-500">Yes</span>@else<span class="text-gray-300">—</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="text-[11px] text-gray-400 mt-3 border-t border-gray-100 pt-3">Roles are enforced on the server. Hiding a menu item is not enough, so every action is checked by a policy before it runs.</p>
        </div>
    </div>

</x-layouts.app>
