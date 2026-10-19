<x-layouts.app title="Register Tenant — Quest Building">

    <x-page-header title="Register tenant"
        subtitle="Creates a tenant account. An activation email is sent automatically." />

    <div class="max-w-xl bg-white rounded-xl border border-gray-200 shadow-xs p-6">
        <form method="POST" action="{{ route('tenants.store') }}" class="space-y-4">
            @csrf

            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2">Account</p>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">First name</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    @error('first_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Middle name (optional)</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    @error('middle_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Last name</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    @error('last_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
                    @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <x-phone-input name="phone" :value="old('phone')" />
            </div>

            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 pt-2 mb-2">Personal info</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Birthdate <span class="text-xs text-gray-500">(Must be 18+)</span></label>
                    <input type="date" name="birthdate" value="{{ old('birthdate') }}"
                           max="{{ now()->subYears(18)->format('Y-m-d') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    @error('birthdate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">ID type</label>
                    <select name="id_type" id="id_type"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Select ID type —</option>
                        @foreach([
                            'National ID'      => 'National ID',
                            'Passport'         => 'Passport',
                            'Student ID'       => 'Student ID',
                            'Driver\'s License' => 'Driver\'s License',
                            'PRC ID'           => 'PRC ID',
                            'UMID'             => 'UMID',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(old('id_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">ID number</label>
                <input type="text" name="id_number" id="id_number" value="{{ old('id_number') }}"
                       placeholder="Enter ID number"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                <p id="id_number_hint" class="text-xs text-gray-400 mt-1 hidden"></p>
            </div>

            <script>
                (function () {
                    var formats = {
                        'National ID':      { placeholder: '1234-5678-9012-3456', hint: '16-digit PhilSys Card Number (PCN) — e.g. 1234-5678-9012-3456' },
                        'Passport':         { placeholder: 'A1234567',            hint: 'Letter followed by 7 digits — e.g. A1234567' },
                        'Student ID':       { placeholder: 'e.g. 2024-00123',     hint: 'Enter your school-issued student ID number' },
                        "Driver's License": { placeholder: 'N01-22-123456',       hint: 'Format: L-YYMMDD-XXXX — e.g. N01-22-123456' },
                        'PRC ID':           { placeholder: '0123456',             hint: '7-digit PRC license/registration number — e.g. 0123456' },
                        'UMID':             { placeholder: '1234-5678901-2',      hint: '12-digit CRN — format ####-#######-# — e.g. 1234-5678901-2' },
                    };

                    var select = document.getElementById('id_type');
                    var input  = document.getElementById('id_number');
                    var hint   = document.getElementById('id_number_hint');

                    function update() {
                        var val = select.value;
                        if (formats[val]) {
                            input.placeholder = formats[val].placeholder;
                            hint.textContent  = formats[val].hint;
                            hint.classList.remove('hidden');
                        } else {
                            input.placeholder = 'Enter ID number';
                            hint.textContent  = '';
                            hint.classList.add('hidden');
                        }
                    }

                    select.addEventListener('change', update);
                    update(); // run on page load to handle old() values
                })();
            </script>

            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 pt-2 mb-2">Emergency contact</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="emergency_name" value="{{ old('emergency_name') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <x-phone-input name="emergency_phone" :value="old('emergency_phone')" label="Phone" />
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    Register tenant
                </button>
                <a href="{{ route('tenants.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>

</x-layouts.app>
