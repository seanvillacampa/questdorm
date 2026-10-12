<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password — Quest Building</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: linear-gradient(135deg, #0d2a24 0%, #145d4b 50%, #1e8670 100%);
        }
    </style>
</head>
<body class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-md">

        {{-- Logo Header --}}
        <div class="mb-8 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 backdrop-blur">
                <div class="text-2xl font-black text-white">Q</div>
            </div>
            <div class="mt-4">
                <div class="text-xl font-extrabold tracking-tight text-white">Quest Building</div>
                <div class="mt-1 text-xs font-semibold uppercase tracking-[0.22em] text-emerald-100/70">Management System</div>
            </div>
        </div>

        {{-- Card --}}
        <div class="rounded-2xl bg-white p-8 shadow-2xl">
            <div class="mb-6">
                <h2 class="text-3xl font-black leading-none tracking-tight text-[#0d1f1a]">Forgot password?</h2>
                <p class="mt-2 text-sm text-slate-500">Enter your email and we'll send you a reset link.</p>
            </div>

            @if(session('success'))
                <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 p-4 flex items-start gap-3">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-emerald-700">{{ session('success') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-red-600 flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            {{ $error }}
                        </p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wide text-slate-600">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           autofocus required
                           placeholder="you@example.com"
                           class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition-all duration-150 focus:border-[#145d4b] focus:outline-none focus:ring-2 focus:ring-[#145d4b]/20" />
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full rounded-xl bg-[#123f35] px-4 py-3.5 text-sm font-extrabold text-white shadow-lg ring-1 ring-[#0a2820] transition-all duration-150 hover:bg-[#0d3228] hover:shadow-xl active:scale-[0.985] flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Send reset link
                    </button>
                </div>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                Remember your password?
                <a href="{{ route('login') }}" class="font-semibold text-[#145d4b] hover:text-[#0d3228] hover:underline">
                    Back to sign in
                </a>
            </p>
        </div>
    </div>
</body>
</html>
