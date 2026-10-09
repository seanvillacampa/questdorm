<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Quest Building</title>
</head>
<body>
    <h1>Create an account</h1>
    <p>For the building owner and employees only. Tenant accounts are created by staff.</p>

    @if ($errors->any())
        <div style="color:red">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <p>
            <label for="first_name">First Name</label><br>
            <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus>
            @error('first_name') <span style="color:red">{{ $message }}</span> @enderror
        </p>

        <p>
            <label for="middle_name">Middle Name (optional)</label><br>
            <input id="middle_name" type="text" name="middle_name" value="{{ old('middle_name') }}">
        </p>

        <p>
            <label for="last_name">Last Name</label><br>
            <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" required>
        </p>

        <p>
            <label for="email">Email</label><br>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
        </p>

        <p>
            <label for="phone">Phone (optional)</label><br>
            <input id="phone" type="text" name="phone" value="{{ old('phone') }}">
        </p>

        <p>
            <label for="role">Role</label><br>
            <select id="role" name="role" required>
                <option value="owner" {{ old('role') === 'owner' ? 'selected' : '' }}>Owner</option>
                <option value="employee" {{ old('role') === 'employee' ? 'selected' : '' }}>Employee</option>
            </select>
        </p>

        <p>
            <label for="password">Password</label><br>
            <input id="password" type="password" name="password" required>
        </p>

        <p>
            <label for="password_confirmation">Confirm password</label><br>
            <input id="password_confirmation" type="password" name="password_confirmation" required>
        </p>

        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="{{ route('login') }}">Log in</a></p>
</body>
</html>
