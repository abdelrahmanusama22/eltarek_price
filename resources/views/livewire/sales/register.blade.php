<div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 animate-fade-in-down">
    <!-- Header Section -->
    <div class="bg-primary px-8 py-10 text-center">
        <h1 class="text-2xl font-bold text-white tracking-tight">Sales Registration</h1>
        <p class="text-red-100 mt-2 text-sm font-medium tracking-wide">Register for El-Tarek Automotive</p>
    </div>

    <!-- Form Section -->
    <div class="p-8">
        @if ($registered)
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded text-sm mb-6 shadow-sm">
                <p class="font-medium">Registration Successful!</p>
                <p class="mt-1">Your account has been created and is awaiting Admin approval. You will be able to log in once an administrator approves your account.</p>
                <div class="mt-4">
                    <a href="{{ route('sales.login') }}" class="text-green-700 font-bold hover:underline">Return to Login</a>
                </div>
            </div>
        @else
            <form wire:submit.prevent="register" class="space-y-4" x-data="passwordCheck()">

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Full Name</label>
                    <input type="text" id="name" wire:model="name" required autofocus
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary placeholder-gray-400 @error('name') border-red-500 @enderror">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                    <input type="email" id="email" wire:model="email" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary placeholder-gray-400 @error('email') border-red-500 @enderror">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">Phone Number</label>
                    <input type="text" id="phone" wire:model="phone" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary placeholder-gray-400 @error('phone') border-red-500 @enderror">
                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Branch -->
                <div>
                    <label for="branch_id" class="block text-sm font-semibold text-gray-700 mb-1">Branch</label>
                    <select id="branch_id" wire:model="branch_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary bg-white @error('branch_id') border-red-500 @enderror">
                        <option value="">Select a Branch</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                    <input type="password" id="password" wire:model="password" @input="password = $event.target.value; checkStrength()" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary placeholder-gray-400 @error('password') border-red-500 @enderror">
                    
                    <!-- Strength Meter UI -->
                    <div x-cloak x-show="password.length > 0" class="mt-2 text-sm font-medium" :class="strengthColor" x-text="strengthText"></div>
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" id="password_confirmation" wire:model="password_confirmation" @input="password_confirmation = $event.target.value" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary placeholder-gray-400 @error('password_confirmation') border-red-500 @enderror">
                    
                    <!-- Match Indicator UI -->
                    <div x-cloak x-show="password_confirmation.length > 0" class="mt-2 text-sm font-medium" 
                        :class="password === password_confirmation ? 'text-green-600' : 'text-red-600'" 
                        x-text="password === password_confirmation ? 'Passwords match' : 'Passwords do not match'">
                    </div>
                    @error('password_confirmation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Submit -->
                <button type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-primary hover:bg-gradient-to-r hover:from-primary hover:to-primary-dark transition-all duration-300 mt-4">
                    Register
                </button>

                <div class="text-center mt-4 text-sm text-gray-600">
                    Already have an account? <a href="{{ route('sales.login') }}" class="text-primary hover:underline font-bold">Log in here</a>
                </div>
            </form>
        @endif
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('passwordCheck', () => ({
            password: '',
            password_confirmation: '',
            strengthText: '',
            strengthColor: '',
            checkStrength() {
                let strength = 0;
                if (this.password.length >= 8) strength += 1;
                if (this.password.match(/[a-z]/) && this.password.match(/[A-Z]/)) strength += 1;
                if (this.password.match(/\d/)) strength += 1;
                if (this.password.match(/[^a-zA-Z\d]/)) strength += 1;

                if (strength <= 1) {
                    this.strengthText = 'Weak Password';
                    this.strengthColor = 'text-red-500';
                } else if (strength === 2 || strength === 3) {
                    this.strengthText = 'Medium Password';
                    this.strengthColor = 'text-yellow-500';
                } else if (strength >= 4) {
                    this.strengthText = 'Strong Password';
                    this.strengthColor = 'text-green-500';
                }
            }
        }));
    });
</script>
