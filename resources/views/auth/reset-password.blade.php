<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Set Password — Quest Building</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: linear-gradient(135deg, #0d2a24 0%, #145d4b 50%, #1e8670 100%);
        }
        .input-field {
            @apply w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 transition-all duration-150 placeholder:text-slate-400 focus:border-[#145d4b] focus:outline-none focus:ring-2 focus:ring-[#145d4b]/20;
        }
        .btn-submit {
            @apply w-full rounded-xl bg-[#123f35] px-4 py-3.5 text-sm font-extrabold text-white shadow-lg transition-all duration-150 hover:bg-[#0d3228] hover:shadow-xl active:scale-[0.985];
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

        {{-- Password Setup Card --}}
        <div class="rounded-2xl bg-white p-8 shadow-2xl">
            <div class="mb-6">
                <h2 class="text-3xl font-black leading-none tracking-tight text-[#0d1f1a]">Set your password</h2>
                <p class="mt-2 text-sm text-slate-500">Choose a strong password to activate your account.</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4">
                    <ul class="space-y-1 text-sm text-red-600">
                        @foreach ($errors->all() as $error)
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}" />

                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wide text-slate-600">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required readonly
                           class="input-field mt-1.5 bg-slate-50">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wide text-slate-600">New Password</label>
                    <input id="password" type="password" name="password" required autofocus
                           placeholder="At least 8 characters"
                           class="input-field mt-1.5">
                    <p class="mt-1.5 text-xs text-slate-500">Use at least 8 characters with a mix of letters and numbers.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wide text-slate-600">Confirm Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                           class="input-field mt-1.5">
                </div>

                <button type="submit" class="btn-submit mt-6">
                    Set password & sign in
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                Already have an account?
                <a href="{{ route('login') }}" class="font-semibold text-[#145d4b] hover:text-[#0d3228] hover:underline">
                    Sign in
                </a>
            </p>
        </div>
    </div>
</body>
</html>
