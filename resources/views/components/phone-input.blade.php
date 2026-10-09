@props(['name' => 'phone', 'value' => '', 'required' => false, 'label' => 'Phone'])

<div>
    <label class="block text-xs font-medium text-gray-700 mb-1">{{ $label }}</label>
    <input type="tel" 
           id="{{ $name }}" 
           name="{{ $name }}" 
           value="{{ $value }}"
           {{ $attributes->merge(['class' => 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500']) }}
           @if($required) required @endif />
    <input type="hidden" id="{{ $name }}_country_code" name="{{ $name }}_country_code" value="+63" />
    @error($name) <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
</div>

@once
    @push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.10/build/css/intlTelInput.css">
    <style>
        .iti { width: 100%; }
        .iti__flag-container { padding: 0; }
        .iti__selected-flag { padding: 0 0 0 8px; }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.10/build/js/intlTelInput.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all phone inputs
            document.querySelectorAll('input[type="tel"]').forEach(function(input) {
                const countryCodeInput = document.getElementById(input.id + '_country_code');
                
                const iti = window.intlTelInput(input, {
                    initialCountry: "ph",
                    preferredCountries: ["ph", "us", "gb"],
                    separateDialCode: true,
                    utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.10/build/js/utils.js",
                    autoPlaceholder: "aggressive",
                    formatOnDisplay: true,
                    nationalMode: true,
                });

                // Update hidden country code field when country changes
                input.addEventListener('countrychange', function() {
                    const countryData = iti.getSelectedCountryData();
                    if (countryCodeInput) {
                        countryCodeInput.value = '+' + countryData.dialCode;
                    }
                });

                // Set initial country code
                const countryData = iti.getSelectedCountryData();
                if (countryCodeInput && countryData) {
                    countryCodeInput.value = '+' + countryData.dialCode;
                }

                // Validate on blur
                input.addEventListener('blur', function() {
                    if (input.value.trim()) {
                        if (iti.isValidNumber()) {
                            input.classList.remove('border-red-300');
                            input.classList.add('border-green-300');
                        } else {
                            input.classList.remove('border-green-300');
                            input.classList.add('border-red-300');
                        }
                    } else {
                        input.classList.remove('border-red-300', 'border-green-300');
                    }
                });
            });
        });
    </script>
    @endpush
@endonce
