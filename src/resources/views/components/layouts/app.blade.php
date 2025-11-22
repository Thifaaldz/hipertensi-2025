<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Sistem Hipertensi Nasional' }}</title>

    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    @livewireStyles
</head>
<body class="bg-blue-50 text-gray-800">

    {{-- HEADER --}}
    <header class="bg-blue-700 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-semibold">Sistem Informasi Hipertensi</h1>
        </div>
    </header>

    {{-- MAIN CONTENT --}}
    <main class="container mx-auto py-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
