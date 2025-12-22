<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>PUK OMRON</title>
     <!-- FAVICON -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="min-h-screen flex items-center justify-center bg-cover bg-center bg-no-repeat"
    style="background-image: url('{{ asset('bg-login.jpg') }}')"
    onload="document.getElementById('password').value=''">


         <!-- Overlay gelap -->
        <div class="absolute inset-0 bg-black/20"></div>

        <!-- Card login -->
        <div class="relative z-10 w-full max-w-sm p-6 bg-white/90
                dark:bg-gray-800/90 backdrop-blur
                rounded-2xl shadow-lg transition-all duration-300 animate-fadeIn">


        <div class="flex justify-center mb-6">
            <img src="{{ asset('puk_omron.PNG') }}"
                alt="PUK OMRON"
                class="h-20 w-auto object-contain">
        </div>


        @if($errors->any())
            <div class="p-3 mb-4 text-sm text-red-600 bg-red-50 dark:bg-red-900/40 rounded-lg">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.process') }}" id="loginForm">
            @csrf

            <!-- Username -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
                <input type="text" name="username"
                       class="w-full p-3 border rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                       required>
            </div>

            <!-- Password -->
            <div class="mb-4 relative">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                <input id="password" type="password" name="password" value=""
                       class="w-full p-3 pr-12 border rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                       required autocomplete="new-password">

                <!-- Icon Toggle -->
                <div
                    onclick="togglePassword()"
                    id="toggleIcon"
                    class="absolute right-3 top-10 cursor-pointer text-gray-500 dark:text-gray-300">
                    👁️
                </div>
            </div>

            <!-- Button LOGIN -->
            <button id="btnLogin"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold transition-all duration-200 disabled:opacity-70">
                Login
            </button>
        </form>
    </div>

    <script>
        const password = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        const btnLogin = document.getElementById('btnLogin');
        const form = document.getElementById('loginForm');

        // Show/Hide Password
        function togglePassword() {
            if (password.type === 'password') {
                password.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                password.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }

        // Loading Button Animation
        form.addEventListener('submit', function () {
            btnLogin.disabled = true;
            btnLogin.innerHTML = `
                <span class="animate-pulse">⏳ Logging in...</span>
            `;
        });
    </script>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn .4s ease-in-out;
        }
    </style>

</body>
</html>
