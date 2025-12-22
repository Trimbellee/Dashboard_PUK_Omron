@extends('layouts.app')
@section('title', 'Dashboard | PUK OMRON')

@section('content')
<div class="p-4 md:p-6">

    <!-- FILTER & REFRESH -->
    <form method="GET" action="{{ route('dashboard') }}" id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Bulan -->
        <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-white">Bulan</label>
            @php $currentMonth = (int) date('m'); @endphp
            <select name="bulan" class="w-full rounded 
                    bg-white dark:bg-slate-800
                    text-gray-900 dark:text-black
                    border-gray-300 dark:border-gray-600
                    focus:ring-blue-500 focus:border-blue-500">
                <option class="bg-white dark:bg-slate-800 text-gray-900 dark:text-black" value="all" {{ ($bulan === '' || $bulan === null) ? 'selected' : '' }}>Semua</option>
                @for ($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ ((int)$bulan === $i || ($bulan === '' && $i === $currentMonth)) ? 'selected' : '' }}>
                        {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                    </option>
                @endfor
            </select>
        </div>

        <!-- Tahun -->
        <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-white">Tahun</label>
            @php $currentYear = (int) date('Y'); @endphp
            <select name="tahun" class="w-full rounded 
                    bg-white dark:bg-slate-800
                    text-gray-900 dark:text-black
                    border-gray-300 dark:border-gray-600
                    focus:ring-blue-500 focus:border-blue-500">
                <option class="bg-white dark:bg-slate-800 text-gray-900 dark:text-black" value="all" {{ ($tahun === '' || $tahun === null) ? 'selected' : '' }}>Semua</option>
                @for ($t = 2025; $t <= $currentYear + 1; $t++)
                    <option value="{{ $t }}" {{ ((int)$tahun === $t || ($tahun === '' && $t === $currentYear)) ? 'selected' : '' }}>{{ $t }}</option>
                @endfor
            </select>
        </div>

        <!-- Bidang -->
        <div>
            <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-white">Bidang</label>
            <select name="bidang" class="w-full rounded 
                    bg-white dark:bg-slate-800
                    text-gray-900 dark:text-black
                    border-gray-300 dark:border-gray-600
                    focus:ring-blue-500 focus:border-blue-500">
                <option class="bg-white dark:bg-slate-800 text-gray-900 dark:text-black" value="" {{ ($bidang === '' || $bidang === null) ? 'selected' : '' }}>Semua</option>
                @foreach ($bidangList as $b)
                    <option value="{{ strtolower($b) }}" {{ strtolower($bidang) === strtolower($b) ? 'selected' : '' }}>
                        {{ $b }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Tombol Filter + Refresh -->
        <div class="flex items-end">
            <button type="submit" id="filterButton" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition flex items-center justify-center">
                <svg id="filterSpinner" class="animate-spin h-5 w-5 mr-2 hidden text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                Terapkan Filter
            </button>
        </div>
    </form>

    <!-- STATISTICS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 
            shadow rounded p-4 text-center
            text-gray-800 dark:text-black">
            <p class="text-sm text-gray-500 dark:text-black">Total Pengajuan</p>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-black" id="card-total">{{ $stat['total'] }}</h2>
            <p class="text-sm mt-1 text-gray-400 dark:text-black" id="card-total-amount">Rp {{ number_format($stat['totalAmount'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/30 
            text-gray-800 dark:text-yellow-100
            shadow rounded p-4 text-center">
            <p class="text-sm text-gray-500 dark:text-black">Menunggu</p>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-black" id="card-menunggu">{{ $stat['menunggu'] }}</h2>
            <p class="text-sm mt-1 text-gray-400 dark:text-black" id="card-menunggu-amount">Rp {{ number_format($stat['menungguAmount'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/30 
            text-gray-800 dark:text-green-100
            shadow rounded p-4 text-center">
            <p class="text-sm text-gray-500 dark:text-black">Disetujui</p>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-black" id="card-disetujui">{{ $stat['disetujui'] }}</h2>
            <p class="text-sm mt-1 text-gray-400 dark:text-black" id="card-disetujui-amount">Rp {{ number_format($stat['disetujuiAmount'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-red-50 dark:bg-red-900/30 
            text-gray-800 dark:text-red-100
            shadow rounded p-4 text-center">
            <p class="text-sm text-gray-500 dark:text-black">Ditolak</p>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-black" id="card-ditolak">{{ $stat['ditolak'] }}</h2>
            <p class="text-sm mt-1 text-gray-400 dark:text-black" id="card-ditolak-amount">Rp {{ number_format($stat['ditolakAmount'], 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- CHARTS -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white dark:bg-slate-800 
            shadow rounded p-4
            text-gray-800 dark:text-black">
            <h3 class="font-semibold mb-3 text-center text-gray-700 dark:text-black">Grafik Jumlah Pengajuan</h3>
            <canvas  id="chartStatus" height="250"></canvas>
        </div>
        <div class="bg-white dark:bg-slate-800 
            shadow rounded p-4
            text-gray-800 dark:text-black">
            <h3 class="font-semibold mb-3 text-center text-gray-700 dark:text-black">Grafik Total Biaya</h3>
            <canvas id="chartBiaya" height="250"></canvas>
        </div>
    </div>

    <!-- CHART PER BIDANG -->
    <div class="bg-white dark:bg-slate-800 
            shadow rounded p-4
            text-gray-800 dark:text-black">
        <h3 class="font-semibold mb-3 text-center text-gray-700 dark:text-black">Biaya Disetujui per Bidang</h3>
        <canvas id="chartByBidang" height="250"></canvas>
    </div>

</div>

<style>
@keyframes spin-slow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.animate-spin-slow { animation: spin-slow 1.5s linear infinite; }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const dark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    const textColor   = dark ? "#000000ff" : "#000000ff";
    const borderColor = dark ? "#4b5563" : "#d1d5db";
    const gridColor   = dark ? "#374151" : "#e5e7eb";
    const c = { menunggu:"#facc15", disetujui:"#22c55e", ditolak:"#ef4444" };

    function animateCounter(id, target, duration = 800){
        const el = document.getElementById(id); let start = 0;
        const step = Math.ceil(target / (duration / 30));
        const timer = setInterval(() => {
            start += step;
            if(start >= target){ el.textContent = target.toLocaleString("id-ID"); clearInterval(timer); }
            else el.textContent = start.toLocaleString("id-ID");
        },30);
    }

    function animateRupiahCounter(id, target, duration = 800){
        const el = document.getElementById(id); let start = 0;
        const step = Math.ceil(target / (duration / 30));
        const timer = setInterval(() => {
            start += step;
            if(start >= target){ el.textContent = "Rp "+target.toLocaleString("id-ID"); clearInterval(timer);}
            else el.textContent = "Rp "+start.toLocaleString("id-ID");
        },30);
    }

    let menunggu = {{ $stat['menunggu'] }};
    let disetujui = {{ $stat['disetujui'] }};
    let ditolak = {{ $stat['ditolak'] }};
    let menungguAmount = {{ $stat['menungguAmount'] }};
    let disetujuiAmount = {{ $stat['disetujuiAmount'] }};
    let ditolakAmount = {{ $stat['ditolakAmount'] }};
    let total = menunggu + disetujui + ditolak;
    let totalAmount = menungguAmount + disetujuiAmount + ditolakAmount;

    // Chart Status
    const chartStatusCtx = document.getElementById("chartStatus").getContext("2d");
    const chartStatus = new Chart(chartStatusCtx, {
        type:"doughnut",
        data:{ labels:["Menunggu","Disetujui","Ditolak"], datasets:[{ data:[menunggu,disetujui,ditolak], backgroundColor:[c.menunggu,c.disetujui,c.ditolak], borderColor:borderColor, borderWidth:2, cutout:"60%" }]},
        options:{ plugins:{ legend:{ labels:{ color:textColor } }, tooltip:{ callbacks:{ label: ctx => { const val=ctx.raw; const pct=((val/total)*100).toFixed(1); return `${ctx.label}: ${val} (${pct}%)`; }}}}}
    });

    // Chart Biaya
    const chartBiayaCtx = document.getElementById("chartBiaya").getContext("2d");
    const chartBiaya = new Chart(chartBiayaCtx, {
        type:"bar",
        data:{
            labels:["Menunggu","Disetujui","Ditolak"],
            datasets:[
                { type:"bar", label:"Total Biaya", data:[menungguAmount,disetujuiAmount,ditolakAmount], backgroundColor:[c.menunggu+"cc",c.disetujui+"cc",c.ditolak+"cc"], borderColor:borderColor,borderWidth:2},
                { type:"line", label:"Persentase Biaya (%)", data:[(menungguAmount/totalAmount)*100,(disetujuiAmount/totalAmount)*100,(ditolakAmount/totalAmount)*100], borderWidth:3, borderColor: dark?"#60a5fa":"#2563eb", tension:0.4, pointRadius:5, pointBackgroundColor: dark?"#93c5fd":"#3b82f6"}
            ]
        },
        options:{
            scales:{ x:{ticks:{color:textColor},grid:{color:gridColor}}, y:{ticks:{color:textColor},grid:{color:gridColor}} },
            plugins:{ legend:{ labels:{ color:textColor } }, tooltip:{ callbacks:{ label:function(ctx){ if(ctx.dataset.type==="line") return `${ctx.label}: ${ctx.raw.toFixed(2)}%`; else return `${ctx.label}: Rp ${ctx.raw.toLocaleString("id-ID")}`; }}}}
        }
    });

    // Chart By Bidang
    const ctxBidang = document.getElementById("chartByBidang").getContext("2d");
    let labelsBidang = {!! json_encode(array_keys($chartData)) !!};
    let valuesBidang = {!! json_encode(array_values($chartData)) !!};
    let chartByBidang = new Chart(ctxBidang, {
        type:'bar',
        data:{ labels:labelsBidang, datasets:[{ label:'Biaya Disetujui (Rp)', data:valuesBidang, backgroundColor:'rgba(26,115,232,0.7)', borderColor:'rgba(26,115,232,1)', borderWidth:1 }] },
        options:{ responsive:true, plugins:{ legend:{ labels:{ color:textColor } } }, scales:{ x:{ ticks:{ color:textColor }, grid:{ color:borderColor } }, y:{ ticks:{ color:textColor, callback: function(value){ return 'Rp '+value.toLocaleString(); } }, grid:{ color:borderColor } } } }
    });

    // Animate counters initially
    animateCounter("card-total", total);
    animateCounter("card-menunggu", menunggu);
    animateCounter("card-disetujui", disetujui);
    animateCounter("card-ditolak", ditolak);
    animateRupiahCounter("card-total-amount", totalAmount);
    animateRupiahCounter("card-menunggu-amount", menungguAmount);
    animateRupiahCounter("card-disetujui-amount", disetujuiAmount);
    animateRupiahCounter("card-ditolak-amount", ditolakAmount);

    // FILTER + REFRESH AJAX
    const filterForm = document.getElementById("filterForm");
    filterForm.addEventListener("submit", function(e){
        e.preventDefault();
        loadData();
    });

    function loadData(){
        const filterButton = document.getElementById("filterButton");
        const filterSpinner = document.getElementById("filterSpinner");

        // Show spinner
        filterButton.disabled = true;
        filterSpinner.classList.remove("hidden");

        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData).toString();
        const statUrl = "{{ env('API_URL') }}?action=getStatistik&" + params;
        const bidangUrl = "{{ env('API_URL') }}?action=getApprovedByBidang&" + params;

        fetch(statUrl)
        .then(res=>res.json())
        .then(data=>{
            menunggu = data.menunggu ?? 0;
            disetujui = data.disetujui ?? 0;
            ditolak = data.ditolak ?? 0;
            menungguAmount = data.menungguAmount ?? 0;
            disetujuiAmount = data.disetujuiAmount ?? 0;
            ditolakAmount = data.ditolakAmount ?? 0;
            total = menunggu + disetujui + ditolak;
            totalAmount = menungguAmount + disetujuiAmount + ditolakAmount;

            animateCounter("card-total", total);
            animateCounter("card-menunggu", menunggu);
            animateCounter("card-disetujui", disetujui);
            animateCounter("card-ditolak", ditolak);
            animateRupiahCounter("card-total-amount", totalAmount);
            animateRupiahCounter("card-menunggu-amount", menungguAmount);
            animateRupiahCounter("card-disetujui-amount", disetujuiAmount);
            animateRupiahCounter("card-ditolak-amount", ditolakAmount);

            chartStatus.data.datasets[0].data = [menunggu, disetujui, ditolak]; chartStatus.update();
            chartBiaya.data.datasets[0].data = [menungguAmount, disetujuiAmount, ditolakAmount];
            chartBiaya.data.datasets[1].data = [(menungguAmount/totalAmount)*100,(disetujuiAmount/totalAmount)*100,(ditolakAmount/totalAmount)*100]; chartBiaya.update();
        })
        .then(()=>{
            return fetch(bidangUrl).then(res=>res.json()).then(bidangData=>{
                const labels = Object.keys(bidangData.approvedByBidang||{});
                const values = Object.values(bidangData.approvedByBidang||{});
                chartByBidang.data.labels = labels;
                chartByBidang.data.datasets[0].data = values;
                chartByBidang.update();
            });
        })
        .catch(err=>{console.error(err); alert("Gagal memuat data statistik.");})
        .finally(()=>{
            filterButton.disabled = false;
            filterSpinner.classList.add("hidden");
        });
    }
});
</script>
@endsection
