<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sistema Geoespacial') - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Styles -->
    @stack('styles')
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo/Brand -->
                    <div class="flex items-center">
                        <a href="{{ url('/') }}" class="flex items-center text-xl font-bold text-gray-900">
                            <i class="fas fa-globe-americas text-blue-600 mr-2"></i>
                            <span>Sistema Geoespacial</span>
                        </a>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden md:flex items-center space-x-6">
                        <a href="{{ url('/dashboard') }}" 
                           class="flex items-center px-3 py-2 text-gray-700 hover:text-blue-600 font-medium transition-colors {{ request()->is('dashboard') ? 'text-blue-600' : '' }}">
                            <i class="fas fa-tachometer-alt mr-2"></i>
                            Dashboard
                        </a>
                        
                        <a href="{{ route('reports.index') }}" 
                           class="flex items-center px-3 py-2 text-gray-700 hover:text-blue-600 font-medium transition-colors {{ request()->is('reports*') ? 'text-blue-600' : '' }}">
                            <i class="fas fa-file-alt mr-2"></i>
                            Reportes LLM
                        </a>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button type="button" class="p-2 text-gray-700 hover:text-blue-600" onclick="toggleMobileMenu()">
                            <i class="fas fa-bars text-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Mobile Navigation -->
                <div id="mobileMenu" class="hidden md:hidden border-t border-gray-200 py-4">
                    <div class="space-y-2">
                        <a href="{{ url('/dashboard') }}" 
                           class="flex items-center px-3 py-2 text-gray-700 hover:text-blue-600 font-medium">
                            <i class="fas fa-tachometer-alt mr-2"></i>
                            Dashboard
                        </a>
                        <a href="{{ route('reports.index') }}" 
                           class="flex items-center px-3 py-2 text-gray-700 hover:text-blue-600 font-medium">
                            <i class="fas fa-file-alt mr-2"></i>
                            Reportes LLM
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-12">
            <div class="container mx-auto px-4 py-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-gray-600 text-sm">
                        © {{ date('Y') }} Sistema Geoespacial. Desarrollado con ❤️ usando Laravel y IA.
                    </div>
                    <div class="flex items-center space-x-4 mt-4 md:mt-0">
                        <span class="text-gray-600 text-sm">Powered by:</span>
                        <div class="flex items-center space-x-2">
                            <i class="fab fa-laravel text-red-500"></i>
                            <span class="text-sm text-gray-600">Laravel</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-robot text-blue-500"></i>
                            <span class="text-sm text-gray-600">LLM</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-database text-green-500"></i>
                            <span class="text-sm text-gray-600">PostGIS</span>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Alpine.js -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <!-- Custom Scripts -->
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const mobileMenu = document.getElementById('mobileMenu');
            const mobileButton = event.target.closest('[onclick="toggleMobileMenu()"]');
            
            if (!mobileButton && !mobileMenu.contains(event.target)) {
                mobileMenu.classList.add('hidden');
            }
        });

        // Flash messages auto-hide
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessages = document.querySelectorAll('[data-flash]');
            flashMessages.forEach(function(message) {
                setTimeout(function() {
                    message.style.opacity = '0';
                    setTimeout(function() {
                        message.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
