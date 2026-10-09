<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password — Quest Building</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center">

<div class="w-full max-w-sm">
    <div class="flex items-center gap-2 justify-center mb-8">
        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold">Q</div>
        <span class="font-semibold text-gray-900">Quest Building</span>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
        <h1 class="text-xl font-bold text-gray-900 mb-1">Reset your password</h1>
        <p class="text-sm text-gray-500 mb-6">Enter your email and we'll send a reset link.</p>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" autofocus required
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit"
                    class="w-full py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                Send reset link
            </button>
        </form>

        <p class="text-center text-xs text-gray-400 mt-6">
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Back to sign in</a>
        </p>
    </div>
</div>

</body>
</html>
