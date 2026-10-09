<!DOCTYPE html>
<html lang="en" class="h-full bg-[#f5f7f6]">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{!! $title ?? 'Quest Building' !!}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
</head>
<body class="min-h-full bg-[#f5f7f6] text-slate-900">
    <div class="flex h-screen overflow-hidden">
        <aside class="scrollbar-hidden sticky top-0 hidden h-screen w-[250px] shrink-0 flex-col overflow-y-auto bg-[#123f35] lg:flex">
            <div class="flex h-20 items-center border-b border-white/10 px-5">
                <div>
                    <div class="text-[15px] font-extrabold tracking-tight text-white">Quest Building</div>
                    <div class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-100/60">Management System</div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-3 py-5">
                <div class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-100/45">Operations</div>
                <nav class="space-y-1">
                    <x-nav-link route="owner.dashboard" :active="request()->routeIs('owner.dashboard','staff.dashboard')">
                        <x-slot:icon><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></x-slot:icon>
                        Dashboard
                    </x-nav-link>
                    <x-nav-link route="invoices.index" :active="request()->routeIs('invoices.index') || request()->routeIs('invoices.show') || request()->routeIs('invoices.latest')">
                        <x-slot:icon><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2zM9 7h6M9 11h6M9 15h3"/></svg></x-slot:icon>
                        Billing Statements
                    </x-nav-link>
                    <x-nav-link route="meter-readings.index" :active="request()->routeIs('meter-readings.*')">
                        <x-slot:icon><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="8"/><path d="M12 5V3M5.6 6.6 4.2 5.2M18.4 6.6l1.4-1.4M12 13l4-3M8 18h8"/></svg></x-slot:icon>
                        Meter readings
                    </x-nav-link>
                    <x-nav-link route="tenant-messages.index" :active="request()->routeIs('tenant-messages.*')">
                        <x-slot:icon><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M12 7v6M12 17h.01"/></svg></x-slot:icon>
                        Concerns
                    </x-nav-link>
                    <x-nav-link route="contracts.index" :active="request()->routeIs('contracts.*')">
                        <x-slot:icon><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9l4 4v16H6zM14 2v5h5M9 12h7M9 16h5"/></svg></x-slot:icon>
                        Contracts
                    </x-nav-link>
                    <x-nav-link route="tenants.index" :active="request()->routeIs('tenants.*')">
                        <x-slot:icon><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM17 11l2 2 4-4"/></svg></x-slot:icon>
                        Tenants
                    </x-nav-link>
                    <x-nav-link route="rooms.index" :active="request()->routeIs('rooms.*')">
                        <x-slot:icon><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V4h11v17M16 9h3v12M8 8h4M8 12h4M8 16h4"/><circle cx="13" cy="16" r="0.5" fill="currentColor"/></svg></x-slot:icon>
                        Rooms
                    </x-nav-link>
                </nav>

                <div class="mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-100/45">Workspace</div>
                <div class="space-y-1">
                    <x-nav-link route="laundry.index" :active="request()->routeIs('laundry.*') && !request()->routeIs('laundry.reports')">
                        <x-slot:icon><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="3"/><path d="M4 8h16M8 5h.01M11 5h.01"/><circle cx="12" cy="15" r="4"/></svg></x-slot:icon>
                        Laundry orders
                    </x-nav-link>
                    <x-nav-link route="detergent.index" :active="request()->routeIs('detergent.*')">
                        <x-slot:icon><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2h6l1 4H8L9 2z"/><path d="M5 6h14l-1.5 13a2 2 0 0 1-2 1.5h-7a2 2 0 0 1-2-1.5L5 6z"/><path d="M9 11v5M12 11v5M15 11v5"/></svg></x-slot:icon>
                        Detergent Inventory
                    </x-nav-link>
                </div>

                @if(auth()->user()?->hasRole('owner'))
                    <div class="mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-100/45">Owner admin</div>
                    <div class="space-y-1">
                        <x-nav-link route="reports.index" :active="request()->routeIs('reports.*')">
                            <x-slot:icon><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M7 11v5M12 8v8M17 6v10"/></svg></x-slot:icon>
                            Reports
                        </x-nav-link>
                        <x-nav-link route="employees.index" :active="request()->routeIs('employees.*')">
                            <x-slot:icon><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM19 8v6M16 11h6"/></svg></x-slot:icon>
                            Employees
                        </x-nav-link>
                        <x-nav-link route="audit-logs.index" :active="request()->routeIs('audit-logs.*')">
                            <x-slot:icon><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M12 18v-6M9 15h6"/></svg></x-slot:icon>
                            Audit logs
                        </x-nav-link>
                        <x-nav-link route="email-notifications.index" :active="request()->routeIs('email-notifications.*')">
                            <x-slot:icon><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="m22 6-10 7L2 6"/></svg></x-slot:icon>
                            Email notifications
                        </x-nav-link>
                        <x-nav-link route="settings.index" :active="request()->routeIs('settings.*')">
                            <x-slot:icon><svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1l2-1.5-2-3.4-2.4 1a8 8 0 0 0-1.8-1L14.4 3h-4l-.4 3a8 8 0 0 0-1.8 1L5.8 6 4 9.4 6 11a7 7 0 0 0 0 2l-2 1.5L6 18l2.4-1a8 8 0 0 0 1.8 1l.4 3h4l.4-3a8 8 0 0 0 1.8-1l2.4 1 2-3.4L19 13a7 7 0 0 0 .1-1Z"/></svg></x-slot:icon>
                            Settings
                        </x-nav-link>
                    </div>
                @endif
            </div>

            <div class="border-t border-white/10 p-4">
                @php $user = auth()->user(); @endphp
                <div class="flex items-center gap-3 rounded-xl bg-white/5 p-3">
                    <div class="flex size-9 items-center justify-center rounded-full bg-[#e9b64e] text-xs font-extrabold text-[#153f35]">
                        {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr(strstr($user->name, ' '), 1, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-xs font-bold text-white">{{ $user->name }}</div>
                        <div class="truncate text-[10px] text-emerald-100/60">{{ $user->primaryRole() }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-white/60 hover:text-white" title="Sign out">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="flex min-h-0 min-w-0 flex-1 flex-col">
            <main class="scrollbar-hidden flex-1 overflow-y-auto p-4 md:p-7">
                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
