<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PUK OMRON</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-black relative overflow-hidden font-inter">

<!-- Background gradient statis (tanpa backdrop-blur, tanpa animasi blur) -->
<div class="absolute inset-0 bg-gradient-to-br from-white/40 via-gray-100/30 to-white/40 dark:from-gray-900/60 dark:via-black dark:to-gray-900/60"></div>

<!-- Soft Light Glow — blur dikurangi, animasi pakai transform saja, tetap ringan di GPU -->
<div class="absolute w-[500px] h-[500px] bg-white/20 rounded-full blur-2xl -top-40 -left-40 will-change-transform animate-slowFloat"></div>
<div class="absolute w-[400px] h-[400px] bg-white/15 rounded-full blur-2xl -bottom-40 -right-40 will-change-transform animate-slowFloatReverse"></div>

<!-- LOGIN CARD -->
<div class="relative z-10
    w-[92%] sm:w-full max-w-md
    p-10
    bg-white/90 dark:bg-gray-900/90
    rounded-3xl
    shadow-[0_25px_80px_rgba(0,0,0,0.25)]
    border border-white/30
    transition-all duration-500
    animate-fadeIn">

    <!-- Logo -->
    <div class="flex justify-center mb-6">
        <img src="{{ asset('puk_omron.PNG') }}"
             width="80" height="80"
             loading="eager"
             class="h-20 w-auto drop-shadow-xl">
    </div>

    <br>
    <br>

    @if($errors->any())
    <div class="p-3 mb-4 text-sm text-red-600 bg-red-50 dark:bg-red-900/40 rounded-lg">
        {{ $errors->first() }}
    </div>
    @endif

    <form method="POST" action="{{ route('login.process') }}" id="loginForm">
        @csrf

        <!-- Username / NIK -->
        <div class="mb-5">
            <label class="block text-sm font-medium mb-2 dark:text-gray-300">Username atau NIK Karyawan</label>
            <input type="text" name="login" required
                placeholder="Masukkan username atau NIK"
                class="w-full p-3 rounded-xl border
                bg-white/90 dark:bg-gray-800/90
                focus:ring-2 focus:ring-blue-500
                focus:outline-none
                transition-colors duration-200
                dark:text-gray-300">
        </div>

        <!-- Password -->
        <div class="mb-6 relative">
            <label class="block text-sm font-medium mb-2 dark:text-gray-300">Password</label>
            <input id="password" type="password" name="password"
                autocomplete="new-password" required
                class="w-full p-3 rounded-xl border
                bg-white/90 dark:bg-gray-800/90
                focus:ring-2 focus:ring-blue-500
                focus:outline-none
                transition-colors duration-200
                dark:text-gray-300">

            <div onclick="togglePassword()"
                id="toggleIcon"
                class="absolute right-4 top-10 cursor-pointer text-gray-500">
                👁️
            </div>
        </div>

        <!-- Button -->
        <div class="flex justify-center">
            <button id="btnLogin"
                class="px-8 py-2.5
                rounded-xl font-semibold text-white text-sm
                bg-blue-500
                hover:bg-blue-600
                transition-colors duration-200
                shadow-md hover:shadow-xl">
                Login
            </button>
        </div>
    </form>

    <p class="text-center text-xs text-gray-500 dark:text-gray-400 mt-8">
        © {{ date('Y') }} PUK OMRON • Internal System
    </p>
</div>

<script>
const password = document.getElementById('password');
const toggleIcon = document.getElementById('toggleIcon');
const btnLogin = document.getElementById('btnLogin');
const form = document.getElementById('loginForm');

function togglePassword() {
    if (password.type === 'password') {
        password.type = 'text';
        toggleIcon.textContent = '🙈';
    } else {
        password.type = 'password';
        toggleIcon.textContent = '👁️';
    }
}

form.addEventListener('submit', function () {
    btnLogin.disabled = true;
    btnLogin.innerHTML = `
    <span class="flex items-center justify-center gap-2">
        <svg class="animate-spin h-5 w-5 text-white"
            xmlns="http://www.w3.org/2000/svg"
            fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10"
                stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        Authenticating...
    </span>
    `;
});
</script>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(25px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
    animation: fadeIn .5s ease-out;
}

/* Animasi transform murni (tidak memicu ulang perhitungan blur tiap frame) */
@keyframes slowFloat {
    0%,100% { transform: translate3d(0,0,0); }
    50% { transform: translate3d(0,-20px,0); }
}
@keyframes slowFloatReverse {
    0%,100% { transform: translate3d(0,0,0); }
    50% { transform: translate3d(0,20px,0); }
}
.animate-slowFloat {
    animation: slowFloat 10s ease-in-out infinite;
}
.animate-slowFloatReverse {
    animation: slowFloatReverse 10s ease-in-out infinite;
}

/* Matikan animasi untuk pengguna yang memilih reduced motion */
@media (prefers-reduced-motion: reduce) {
    .animate-slowFloat,
    .animate-slowFloatReverse,
    .animate-fadeIn {
        animation: none;
    }
}
</style>

</body>
</html>