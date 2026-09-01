<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Portal - El Tarek Pricing</title>
    
    <!-- Tailwind CSS (Play CDN for rapid UI) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Tailwind Configuration for Red/White Theme -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#dc2626', // Red-600
                        'primary-dark': '#b91c1c', // Red-700
                        'primary-light': '#fef2f2', // Red-50
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Optional: Google Fonts for cleaner typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased min-h-screen flex flex-col pb-16 md:pb-0">

    <!-- Header Navigation -->
    <header class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo & Branding -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ route('sales.dashboard') }}" class="flex items-center gap-2 group">
                        <img src="{{ asset('images/logo (2).png') }}" alt="El-Tarek Automotive Logo" class="h-10 w-auto object-contain">
                        <span class="font-bold text-xl text-gray-900 tracking-tight hidden sm:block">El-Tarek Automotive</span>
                    </a>
                </div>

                <!-- User Profile & Logout -->
                @auth('sales')
                <div class="hidden md:flex items-center gap-6">
                    <a href="{{ route('sales.offers') }}" class="flex items-center gap-2 px-3 py-1.5 bg-red-50 text-primary font-semibold rounded-full hover:bg-red-100 transition-colors border border-red-100 shadow-sm group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 group-hover:scale-110 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm">Hot Deals</span>
                    </a>
                    <div class="hidden sm:flex flex-col text-right border-l border-gray-200 pl-6">
                        <span class="text-sm font-semibold text-gray-900">{{ Auth::guard('sales')->user()->name }}</span>
                        <span class="text-xs text-gray-500">Sales Representative</span>
                    </div>
                    <form method="POST" action="{{ route('sales.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="p-2 text-gray-500 hover:text-primary transition-colors focus:outline-none" title="Log Out">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        @yield('content')
        @isset($slot)
            {{ $slot }}
        @endisset
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-sm text-gray-500">
                &copy; 2026 El-Tarek Automotive. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- Mobile Bottom Navigation -->
    @auth('sales')
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] py-3 px-6 z-50 flex justify-around items-center md:hidden">
        <a href="{{ route('sales.dashboard') }}" class="flex flex-col items-center {{ request()->routeIs('sales.dashboard') ? 'text-primary' : 'text-gray-500 hover:text-primary' }} transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="text-[10px] font-medium mt-1">Home</span>
        </a>
        <a href="{{ route('sales.offers') }}" class="flex flex-col items-center {{ request()->routeIs('sales.offers') ? 'text-primary' : 'text-gray-500 hover:text-primary' }} transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
            </svg>
            <span class="text-[10px] font-medium mt-1 text-center leading-none">Hot<br>Deals</span>
        </a>
        <form method="POST" action="{{ route('sales.logout') }}" class="flex flex-col items-center text-gray-500 hover:text-primary transition-colors cursor-pointer m-0 p-0">
            @csrf
            <button type="submit" class="flex flex-col items-center focus:outline-none bg-transparent border-none p-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="text-[10px] font-medium mt-1">Logout</span>
            </button>
        </form>
    </div>
    @endauth

</body>
</html>
