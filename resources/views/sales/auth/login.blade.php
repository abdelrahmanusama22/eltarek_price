<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sales Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#dc2626',
                        'primary-dark': '#b91c1c',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    animation: {
                        'fade-in-down': 'fadeInDown 0.7s ease-out'
                    },
                    keyframes: {
                        fadeInDown: {
                            '0%': { opacity: '0', transform: 'translateY(-20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 animate-fade-in-down">
        
        <!-- Header Section -->
        <div class="bg-primary px-8 py-10 text-center">
            <img src="{{ asset('images/logo (2).png') }}" alt="El-Tarek Automotive Logo" class="h-12 w-auto mx-auto object-contain bg-white p-1 rounded mb-4 shadow-md">
            <h1 class="text-2xl font-bold text-white tracking-tight">El-Tarek Automotive</h1>
            <p class="text-red-100 mt-2 text-sm font-medium tracking-wide uppercase">Sales Representative Portal</p>
        </div>

        <!-- Form Section -->
        <div class="p-8">
            <form method="POST" action="{{ route('sales.login.post') }}" class="space-y-6">
                @csrf
                
                @if ($errors->any())
                    <div class="bg-red-50 border-l-4 border-primary text-primary-dark p-4 rounded text-sm mb-6 shadow-sm">
                        <ul class="list-disc pl-4 space-y-1 font-medium">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:scale-[1.02] hover:shadow-md transition-all duration-300 placeholder-gray-400"
                        placeholder="you@eltarek.com">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" id="password" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:scale-[1.02] hover:shadow-md transition-all duration-300 placeholder-gray-400"
                        placeholder="••••••••">
                </div>
                
                <!-- Remember Me -->
                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="remember" class="ml-2 block text-sm text-gray-600">
                        Remember me
                    </label>
                </div>

                <!-- Submit -->
                <button type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-primary hover:bg-gradient-to-r hover:from-primary hover:to-primary-dark hover:-translate-y-1 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all duration-300 mt-2">
                    Sign In to Portal
                </button>

                <!-- Registration Link -->
                <div class="mt-4 text-center text-sm text-gray-600">
                    Don't have an account? 
                    <a href="{{ route('sales.register') }}" class="font-medium text-primary hover:text-primary-dark hover:underline transition-colors duration-200">
                        Register here
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="bg-gray-50 border-t border-gray-100 px-8 py-4 text-center">
            <p class="text-xs text-gray-500 font-medium">&copy; {{ date('Y') }} El Tarek Auto. Authorized Personnel Only.</p>
        </div>
    </div>

</body>
</html>
