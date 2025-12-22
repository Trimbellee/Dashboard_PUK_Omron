<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>@yield('title', 'Dashboard | PUK OMRON')</title>

    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>


<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">

    <!-- ========== MOBILE OVERLAY (blur + translucent) ========== -->
    <div id="overlay"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm z-30 hidden md:hidden">
    </div>


    <!-- ========== SIDEBAR ========== -->
    <aside id="sidebar"
        class="fixed top-0 left-0 h-screen w-64 bg-white dark:bg-gray-800 shadow-xl
               border-r border-gray-200 dark:border-gray-700 flex flex-col z-40
               transition-all duration-300 ease-in-out
               -translate-x-64 md:translate-x-0 md:w-64 md:hover:w-64 md:group">

        <!-- LOGO -->
        <div class="h-16 px-4 border-b border-gray-200 dark:border-gray-700
            flex items-center justify-center bg-transparent">
            <img src="/puk_omron.PNG" alt="Logo"
            class="h-10 object-contain select-none opacity-90">
        </div>


        <!-- NAV MENU -->
<nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto text-sm font-medium">

    <a href="{{ route('dashboard') }}"
        class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all
        {{ request()->routeIs('dashboard') 
            ? 'bg-blue-600 text-white shadow-md' 
            : 'hover:bg-gray-200 dark:hover:bg-gray-700' }}">
        🏠 <span class="sidebar-text">Dashboard</span>
    </a>

    <a href="{{ route('pengajuan.index') }}"
        class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all
        {{ request()->routeIs('pengajuan.*') 
            ? 'bg-blue-600 text-white shadow-md' 
            : 'hover:bg-gray-200 dark:hover:bg-gray-700' }}">
        📄 <span class="sidebar-text">Pengajuan Dana</span>
    </a>

    <a href="{{ route('kode-surat.index') }}"
    class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all
    {{ request()->routeIs('kode-surat.*') 
        ? 'bg-blue-600 text-white shadow-md' 
        : 'hover:bg-gray-200 dark:hover:bg-gray-700' }}">

    🧾 <span class="sidebar-text">Kode Penomoran Surat</span>
    </a>


    @if(in_array(session('user')['role'], ['Ketua','Sekretaris']))
    <a href="{{ route('users.index') }}"
        class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all
        {{ request()->routeIs('users.*') 
            ? 'bg-blue-600 text-white shadow-md' 
            : 'hover:bg-gray-200 dark:hover:bg-gray-700' }}">
        👥 <span class="sidebar-text">User Management</span>
    </a>
    @endif

</nav>


        <!-- LOGOUT -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('logout') }}"
               class="block w-full text-center py-2 rounded-lg bg-red-500 text-white 
                      font-semibold hover:bg-red-600 transition">
                Logout
            </a>
        </div>

    </aside>



    <!-- ========== MAIN CONTENT WRAPPER ========== -->
    <div id="main" class="md:ml-64 transition-all duration-300 flex flex-col min-h-screen">

        <!-- HEADER -->
        <header id="header"
    class="fixed top-0 left-0 md:left-64 right-0 h-16 bg-white dark:bg-gray-800
           border-b border-gray-200 dark:border-gray-700 shadow-sm 
           flex items-center justify-between px-6 z-30 transition-all duration-300">


            <!-- MOBILE HAMBURGER -->
            <button id="menuBtn"
                class="md:hidden text-2xl mr-3 focus:outline-none">
                ☰
            </button>

            <!-- DATE + TIME -->
            <div class="flex items-center gap-6">
                <div class="text-sm text-gray-600 dark:text-gray-300 leading-tight">
                    <div id="header-day" class="font-semibold"></div>
                    <div id="header-date-time" class="text-xs"></div>
                </div>
            </div>

            <!-- USER INFO -->
            <div class="flex items-center gap-3">
                <div class="text-right leading-tight">
                    <div class="font-semibold">{{ session('user')['username'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ session('user')['role'] }}
                    </div>
                </div>

                <div class="w-10 h-10 flex items-center justify-center rounded-full
                            bg-blue-600 text-white font-bold uppercase">
                    {{ strtoupper(substr(session('user')['username'], 0, 1)) }}
                </div>
            </div>

        </header>


        <!-- PAGE CONTENT -->
        <main class="flex-1 pt-20 p-6 overflow-y-auto">
            @yield('content')
        </main>

    </div>




    <!-- ========== CLOCK SCRIPT ========== -->
    <script>
        function updateHeaderClock() {
            const now = new Date();
            const dayName = now.toLocaleDateString("id-ID", { weekday: "long" });
            const dateStr = now.toLocaleDateString("id-ID", {
                day: "numeric", month: "long", year: "numeric"
            });
            const timeStr = now.toLocaleTimeString("id-ID", {
                hour: "2-digit", minute: "2-digit", second: "2-digit"
            });

            document.getElementById("header-day").textContent = dayName;
            document.getElementById("header-date-time").textContent = `${dateStr} • ${timeStr}`;
        }
        setInterval(updateHeaderClock, 1000);
        updateHeaderClock();
    </script>



    <!-- ========== SIDEBAR INTERACTION SCRIPT ========== -->
    <script>
        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("overlay");
        const menuBtn = document.getElementById("menuBtn");
        const main = document.getElementById("main");

        // Mobile open
        menuBtn.addEventListener("click", () => {
            sidebar.classList.remove("-translate-x-64");
            overlay.classList.remove("hidden");
        });

        // Click outside closes sidebar (mobile only)
        overlay.addEventListener("click", () => {
            sidebar.classList.add("-translate-x-64");
            overlay.classList.add("hidden");
        });

        // Desktop compact mode (auto collapse)
        sidebar.addEventListener("mouseenter", () => {
        sidebar.classList.remove("md:w-20");
        sidebar.classList.add("md:w-64");

        });

        sidebar.addEventListener("mouseleave", () => {
        sidebar.classList.remove("md:w-64");
        sidebar.classList.add("md:w-20");

          
        });
        // Apply compact mode only on desktop:
       function applyDesktopCompact() {
            if (window.innerWidth >= 768) {
                // Sidebar compact mode
                sidebar.classList.add("md:w-20");
                main.classList.add("md:ml-20");

                // Sesuaikan header
                document.getElementById("header").style.left = "16em"; // 20*4px = 80px
            } else {
                // Mobile / full sidebar
                sidebar.classList.remove("md:w-20");
                main.classList.remove("md:ml-20");

                document.getElementById("header").style.left = "0";
            }
        }


        window.addEventListener("resize", applyDesktopCompact);
        applyDesktopCompact();
    </script>


   <style>
@media (min-width:768px) {
    #sidebar.md\:w-20 nav a {
        justify-content: flex-start;
    }

    #sidebar.md\:w-20 nav a span {
        white-space: nowrap;
        font-size: 0.875rem;
    }
}
</style>

</body>
</html>
