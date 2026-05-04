<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-8">
                        <a href="/admin" class="flex items-center">
                            <span class="text-xl font-bold text-indigo-600">Admin Panel</span>
                        </a>
                        <a href="/admin" class="text-gray-600 hover:text-indigo-600 {{ request()->is('admin') ? 'text-indigo-600 font-medium' : '' }}">
                            Dashboard
                        </a>
                        <a href="/admin/products" class="text-gray-600 hover:text-indigo-600 {{ request()->is('admin/products*') ? 'text-indigo-600 font-medium' : '' }}">
                            Products
                        </a>
                        <a href="/admin/orders" class="text-gray-600 hover:text-indigo-600 {{ request()->is('admin/orders*') ? 'text-indigo-600 font-medium' : '' }}">
                            Orders
                        </a>
                        <a href="/admin/categories" class="text-gray-600 hover:text-indigo-600 {{ request()->is('admin/categories*') ? 'text-indigo-600 font-medium' : '' }}">
                            Categories
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="/" class="text-gray-600 hover:text-indigo-600 text-sm">View Site</a>
                        @auth
                            <span class="text-gray-600 text-sm">{{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-red-500 hover:text-red-600 text-sm">Logout</button>
                            </form>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 px-4">
            @inertia
        </main>
    </div>
</body>
</html>