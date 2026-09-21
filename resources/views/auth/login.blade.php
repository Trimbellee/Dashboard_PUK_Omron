<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>PUK OMRON</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<script>
    // Terapkan tema sebelum render agar tidak ada flash putih/hitam.
    // Sesuaikan key 'theme' ini jika toggle tema di admin panel Anda pakai nama key lain.
    (function () {
        const saved = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (saved === 'dark' || (!saved && prefersDark)) {
            document.documentElement.classList.add('dark');
        }
    })();
</script>
</head>

<body class="min-h-screen flex items-center justify-center
    bg-gray-100 dark:bg-black
    relative overflow-hidden font-inter
    px-4 py-8 sm:px-6"
    style="padding-top: max(2rem, env(safe-area-inset-top)); padding-bottom: max(2rem, env(safe-area-inset-bottom));">

<!-- Background gradient statis (tanpa backdrop-blur, tanpa animasi blur) -->
<div class="absolute inset-0 bg-gradient-to-br from-white/40 via-gray-100/30 to-white/40 dark:from-gray-950 dark:via-black dark:to-gray-950"></div>

<!-- Soft Light Glow — blur ringan, animasi transform saja -->
<div class="absolute w-[300px] h-[300px] sm:w-[500px] sm:h-[500px] bg-blue-200/30 dark:bg-blue-500/10 rounded-full blur-2xl -top-24 -left-24 sm:-top-40 sm:-left-40 will-change-transform animate-slowFloat"></div>
<div class="absolute w-[250px] h-[250px] sm:w-[400px] sm:h-[400px] bg-white/20 dark:bg-blue-400/5 rounded-full blur-2xl -bottom-24 -right-24 sm:-bottom-40 sm:-right-40 will-change-transform animate-slowFloatReverse"></div>

<!-- LOGIN CARD -->
<div class="relative z-10
    w-full max-w-[22rem] sm:max-w-md
    p-6 sm:p-10
    bg-white/95 dark:bg-gray-900/90
    rounded-2xl sm:rounded-3xl
    shadow-[0_20px_60px_rgba(0,0,0,0.15)] dark:shadow-[0_20px_60px_rgba(0,0,0,0.6)]
    border border-black/5 dark:border-white/10
    transition-all duration-500
    animate-fadeIn">

    <!-- Logo -->
    <div class="flex justify-center mb-4 sm:mb-6">
        <img src="{{ asset('puk_omron.PNG') }}"
             width="80" height="80"
             loading="eager"
             class="h-14 sm:h-20 w-auto drop-shadow-xl">
    </div>

    <div class="h-4 sm:h-8"></div>

    @if($errors->any())
    <div class="p-3 mb-4 text-sm text-red-700 bg-red-50 border border-red-100 dark:text-red-300 dark:bg-red-900/30 dark:border-red-900/50 rounded-lg">
        {{ $errors->first() }}
    </div>
    @endif

    <form method="POST" action="{{ route('login.process') }}" id="loginForm">
        @csrf

        <!-- Username / NIK -->
        <div class="mb-4 sm:mb-5">
            <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Username atau NIK Karyawan</label>
            <input type="text" name="login" required
                placeholder="Masukkan username atau NIK"
                class="w-full p-3 rounded-xl border text-base
                bg-white dark:bg-gray-800
                border-gray-200 dark:border-gray-700
                text-gray-900 dark:text-gray-100
                placeholder-gray-400 dark:placeholder-gray-500
                focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                focus:outline-none
                transition-colors duration-200">
        </div>

        <!-- Password -->
        <div class="mb-5 sm:mb-6 relative">
            <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Password</label>
            <input id="password" type="password" name="password"
                autocomplete="new-password" required
                class="w-full p-3 pr-11 rounded-xl border text-base
                bg-white dark:bg-gray-800
                border-gray-200 dark:border-gray-700
                text-gray-900 dark:text-gray-100
                placeholder-gray-400 dark:placeholder-gray-500
                focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                focus:outline-none
                transition-colors duration-200">

            <div onclick="togglePassword()"
                id="toggleIcon"
                class="absolute right-4 top-[2.6rem] cursor-pointer select-none text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">
                👁️
            </div>
        </div>

        <!-- Button -->
        <div class="flex justify-center">
            <button id="btnLogin"
                class="w-full sm:w-auto px-8 py-3 sm:py-2.5
                rounded-xl font-semibold text-white text-sm
                bg-blue-600 dark:bg-blue-500
                hover:bg-blue-700 dark:hover:bg-blue-400
                active:scale-[0.98]
                transition-all duration-200
                shadow-md hover:shadow-xl">
                Login
            </button>
        </div>
    </form>

    <p class="text-center text-xs text-gray-500 dark:text-gray-400 mt-6 sm:mt-8">
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
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
    animation: fadeIn .5s ease-out;
}

/* Animasi transform murni, ringan di GPU */
@keyframes slowFloat {
    0%,100% { transform: translate3d(0,0,0); }
    50% { transform: translate3d(0,-16px,0); }
}
@keyframes slowFloatReverse {
    0%,100% { transform: translate3d(0,0,0); }
    50% { transform: translate3d(0,16px,0); }
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

/* Cegah zoom otomatis di iOS saat fokus ke input (font di bawah 16px memicu zoom) */
input[type="text"], input[type="password"] {
    font-size: 16px;
}
</style>

</body>
</html>