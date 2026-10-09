<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign in — Quest Building</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { margin:0; min-height:100vh; background:#fff; display:flex; }

        /* Animated gradient orbs on brand panel */
        @keyframes floatA { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(12px,-18px) scale(1.06)} }
        @keyframes floatB { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(-10px,14px) scale(1.04)} }
        @keyframes floatC { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(8px,10px) scale(1.03)} }
        .orb-a { animation: floatA 9s ease-in-out infinite; }
        .orb-b { animation: floatB 11s ease-in-out infinite; }
        .orb-c { animation: floatC 13s ease-in-out infinite; }

        /* Stat card shimmer */
        @keyframes shimmer { from{opacity:.7} to{opacity:1} }
        .stat-card { animation: shimmer 3s ease-in-out infinite alternate; }
        .stat-card:nth-child(2) { animation-delay:.8s; }
        .stat-card:nth-child(3) { animation-delay:1.6s; }

        /* Input focus ring */
        .field-input:focus {
            outline: none;
            border-color: #14604d;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(20,96,77,0.12);
        }

        /* Submit button press effect */
        .btn-signin:active { transform: translateY(1px); }

        /* Entrance animation for the form card */
        @keyframes slideUp { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
        .form-card { animation: slideUp .55s cubic-bezier(.22,1,.36,1) both; }
    </style>
</head>
<body class="h-full font-sans antialiased">
<div class="flex min-h-screen w-full">

    {{-- ─── Brand / illustration panel ─────────────────────────────────── --}}
    <div class="relative hidden overflow-hidden bg-[#0d3d34] lg:flex" style="width:65%;flex-shrink:0">

        {{-- Decorative blobs --}}
        <div class="orb-a absolute -top-24 -left-24 h-80 w-80 rounded-full bg-[#f5ba45]/10 blur-3xl"></div>
        <div class="orb-b absolute bottom-10 right-0 h-96 w-96 rounded-full bg-white/5 blur-3xl"></div>
        <div class="orb-c absolute top-1/2 left-1/2 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-[#1a6b57]/40 blur-3xl"></div>

        {{-- Subtle grid overlay --}}
        <div class="absolute inset-0 opacity-[0.035]"
             style="background-image:linear-gradient(rgba(255,255,255,0.6) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.6) 1px,transparent 1px);background-size:40px 40px">
        </div>

        <div class="relative z-10 flex w-full flex-col justify-between p-12 text-white">
            {{-- Logo --}}
            <div class="flex items-center gap-3">
                <div class="flex size-11 items-center justify-center rounded-xl bg-[#f5ba45] text-[#103d34] shadow-lg">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 21V5l8-3 8 3v16M9 21v-4h6v4M8 7h2m4 0h2M8 11h2m4 0h2"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[15px] font-extrabold tracking-tight">Quest Building</div>
                    <div class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-100/70">Management System</div>
                </div>
            </div>

            {{-- Headline --}}
            <div class="max-w-[520px]">
                <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-emerald-200/60">Residence operations</p>
                <h1 class="mt-5 text-[54px] font-black leading-[0.93] tracking-[-0.065em] text-white">
                    Manage rooms,<br>
                    payments, laundry, and<br>
                    tenants in one place.
                </h1>
                <p class="mt-6 max-w-[400px] text-[17px] leading-[1.75] text-emerald-50/75">
                    Track billing, monitor deposits, and keep every resident account up to date from a single dashboard.
                </p>

                {{-- Feature bullets --}}
                <div class="mt-9 space-y-3 text-[14px] text-emerald-50/85">

                </div>

                {{-- Mini stat cards – decorative --}}
                <div class="mt-10 flex gap-3">



                </div>
            </div>

            {{-- Footer note --}}
            <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-100/50">
                Secure access · Owners · Employees · Tenants
            </div>
        </div>
    </div>

    {{-- ─── Login form panel ────────────────────────────────────────────── --}}
    <main class="flex items-center justify-center bg-white px-6 py-10" style="width:35%;flex-shrink:0">
        <div class="form-card w-full max-w-[380px]">

            {{-- Mobile-only logo --}}
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <div class="flex size-10 items-center justify-center rounded-xl bg-[#123f35] text-[#f5ba45]">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 21V5l8-3 8 3v16M9 21v-4h6v4M8 7h2m4 0h2M8 11h2m4 0h2"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[15px] font-extrabold tracking-tight text-[#0d2330]">Quest Building</div>
                    <div class="mt-0.5 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Management System</div>
                </div>
            </div>

            {{-- Card --}}
            <div class="rounded-2xl border border-slate-200/80 bg-white px-8 py-9 shadow-[0_2px_8px_rgba(15,23,42,0.05),0_0_0_1px_rgba(15,23,42,0.03)]">

                {{-- Header --}}
                <div class="mb-7">
                    <div class="mb-2 flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-[#123f35]">
                            <svg class="size-4 text-[#f5ba45]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">Secure sign-in</span>
                    </div>
                    <h2 class="text-[42px] font-black leading-none tracking-[-0.06em] text-[#0d1f1a]">Welcome back</h2>
                    <p class="mt-2.5 text-[14px] leading-snug text-slate-500">Sign in to access your Quest Building account.</p>
                </div>

                {{-- Divider --}}
                <div class="mb-6 h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>

                {{-- Flash messages --}}
                @if(session('success'))
                    <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                        <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        <div>
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Form --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500">
                            Email address
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="field-input w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-[14px] text-slate-800 transition-all duration-150 placeholder:text-slate-300"
                               placeholder="you@example.com" />
                    </div>

                    {{-- Password --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500">
                            Password
                        </label>
                        <div class="relative">
                            <input id="password-input" type="password" name="password" required
                                   class="field-input w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-[14px] text-slate-800 transition-all duration-150 placeholder:text-slate-300"
                                   placeholder="••••••••" />
                            <button type="button" id="toggle-pw"
                                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 transition hover:text-slate-600"
                                    aria-label="Toggle password visibility">
                                <svg id="eye-icon" class="size-[18px]" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="1.8">
                                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg id="eye-off-icon" class="size-[18px] hidden" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="1.8">
                                    <path d="M17.94 17.94A10.07 10.07 0 0112 20c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029M9.9 4.24A9.12 9.12 0 0112 4c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-2.496 3.838M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Remember / Forgot --}}
                    <div class="flex items-center justify-between gap-4">
                        <label class="flex cursor-pointer items-center gap-2 text-[13px] text-slate-600 select-none">
                            <input type="checkbox" name="remember"
                                   class="size-4 rounded border-slate-300 text-[#123f35] focus:ring-[#123f35] accent-[#123f35]" />
                            Remember me
                        </label>
                        <a href="{{ route('password.request') }}"
                           class="text-[13px] font-semibold text-[#14604d] transition hover:text-[#0d3d34] hover:underline underline-offset-2">
                            Forgot password?
                        </a>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            class="btn-signin mt-1 w-full rounded-xl bg-[#123f35] px-4 py-3.5 text-[14px] font-extrabold text-white shadow-[0_6px_20px_rgba(18,63,53,0.28)] transition-all duration-150 hover:bg-[#0d3228] hover:shadow-[0_8px_24px_rgba(18,63,53,0.35)] active:scale-[0.985]">
                        Sign in to Quest Building
                    </button>
                </form>

            </div>{{-- /card --}}

            {{-- Footer --}}
            <p class="mt-6 text-center text-[12px] leading-6 text-slate-400">
                Owners, employees and tenants all sign in here.<br>
                <span class="font-medium text-slate-500">You'll be redirected to the correct dashboard.</span>
            </p>

        </div>
    </main>

</div>

<script>
    const btn  = document.getElementById('toggle-pw');
    const inp  = document.getElementById('password-input');
    const eye  = document.getElementById('eye-icon');
    const eyeO = document.getElementById('eye-off-icon');
    btn.addEventListener('click', () => {
        const show = inp.type === 'password';
        inp.type   = show ? 'text' : 'password';
        eye.classList.toggle('hidden', show);
        eyeO.classList.toggle('hidden', !show);
    });
</script>
</body>
</html>
