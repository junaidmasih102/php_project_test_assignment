<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Purchase Form' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen text-gray-900 antialiased">
    @auth
        <nav class="bg-white shadow">
            <div class="max-w-5xl mx-auto px-6 py-3 flex items-center justify-between">
                <div class="flex items-center gap-4 text-sm">
                    <a href="{{ route('purchases.index') }}" wire:navigate class="font-semibold">Purchases</a>
                    @can('create', App\Models\Purchase::class)
                        <a href="{{ route('purchases.create') }}" wire:navigate class="text-blue-600 hover:underline">New</a>
                    @endcan
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-gray-600">
                        {{ auth()->user()->name }}
                        <span class="ml-1 inline-block rounded bg-gray-200 px-2 py-0.5 text-xs uppercase">
                            {{ auth()->user()->role }}
                        </span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-red-600 hover:underline">Logout</button>
                    </form>
                </div>
            </div>
        </nav>
    @endauth

    {{ $slot }}
</body>
</html>
