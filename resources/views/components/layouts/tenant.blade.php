<!DOCTYPE html>
<html lang="en" class="h-full bg-[#f5f7f6]">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{!! $title ?? 'Quest Building' !!}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#f5f7f6] text-slate-900">
    <header class="border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-5xl items-center gap-5 px-4">
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-xl bg-[#145d4b] text-sm font-bold text-white">Q</div>
                <div>
                    <div class="text-sm font-extrabold tracking-tight text-slate-900">Quest Building</div>
                    <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-700/75">Tenant portal</div>
                </div>
            </div>

            @php
                $links = [
                    'tenant.bill'     => 'My Bill',
                    'tenant.payments' => 'Payment History',
                    'tenant.messages' => 'Messages',
                    'tenant.deposit'  => 'Deposit',
                    'tenant.contract' => 'Contract',
                ];
            @endphp
            <nav class="hidden items-center gap-1 md:flex">
                @foreach($links as $route => $label)
                    <a href="{{ route($route) }}"
                       class="relative rounded-lg px-3 py-2 text-sm font-semibold transition-colors {{ request()->routeIs($route, $route . '.*')
                            ? 'bg-emerald-50 text-[#145d4b]'
                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                        {{ $label }}
                        @if($route === 'tenant.messages' && isset($unreadMessagesCount) && $unreadMessagesCount > 0)
                            <span class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white">
                                {{ $unreadMessagesCount > 9 ? '9+' : $unreadMessagesCount }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-3">
                @php $u = auth()->user(); @endphp
                <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1.5">
                    <div class="flex size-7 items-center justify-center rounded-full bg-[#dff6ee] text-[10px] font-bold text-[#145d4b]">
                        {{ strtoupper(substr($u->name,0,1)) }}
                    </div>
                    <span class="text-sm font-semibold text-slate-700">{{ $u->name }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-500 hover:text-red-500">Sign out</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
        {{ $slot }}
    </main>
</body>
</html>
