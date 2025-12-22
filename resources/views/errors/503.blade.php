<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem Sibuk</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100">

    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md text-center">
        <div class="text-yellow-500 text-6xl mb-4">⏳</div>
        <h1 class="text-2xl font-bold mb-2">Sistem Sedang Sibuk</h1>

        <p class="text-gray-600 mb-6">
            Server memerlukan waktu lebih lama.  
            Silakan refresh atau coba kembali nanti.
        </p>

        <button onclick="location.reload()"
            class="px-5 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition">
            Muat Ulang
        </button>
    </div>

</body>
</html>
