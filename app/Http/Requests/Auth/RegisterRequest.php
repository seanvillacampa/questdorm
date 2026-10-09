<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Guest-only route (see routes/web.php); nothing to gate here.
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_country_code' => ['nullable', 'string', 'max:5'],
            'password' => ['required', 'confirmed', Password::min(8)],

            // Only these two roles may self-register through this form.
            // Tenants are registered by staff, never here — see
            // RegisterController::store() for the matching guard.
            'role' => ['required', 'in:owner,employee'],
        ];
    }

    /**
     * Additional validation to ensure the combination of first_name,
     * middle_name, and last_name is unique across all users.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $query = \App\Models\User::where('first_name', $this->first_name)
                ->where('last_name', $this->last_name);
            
            // Handle middle name comparison (both null or both equal)
            if (empty($this->middle_name)) {
                $query->whereNull('middle_name');
            } else {
                $query->where('middle_name', $this->middle_name);
            }
            
            $exists = $query->exists();

            if ($exists) {
                $validator->errors()->add('first_name', 'A user with this exact name already exists.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'role.in' => 'Tenant accounts cannot be created here. An employee or the owner must register tenants from the staff dashboard.',
        ];
    }
}
