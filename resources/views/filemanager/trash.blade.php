@extends('layouts.app')

@section('title','Trash')

@section('content')

<div class="flex flex-col h-[calc(100vh-80px)] overflow-hidden">

    {{-- HEADER --}}
    <div class="shrink-0 flex flex-col gap-4 mb-3">

        <div class="flex items-start justify-between gap-4">

            <div>
                <h1 class="text-2xl md:text-3xl font-bold flex items-center gap-2">
                    🗑 Trash
                </h1>

                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    File yang dihapus akan tersimpan sementara sebelum dihapus permanen
                </p>
            </div>

            <div class="flex items-center gap-2 shrink-0">

                <a href="/file-manager"
                class="h-10 px-4 rounded-xl border
                       border-gray-200 dark:border-gray-700
                       bg-white dark:bg-gray-900
                       hover:bg-gray-100 dark:hover:bg-gray-800
                       flex items-center text-sm transition">

                    ← Kembali

                </a>

            </div>

        </div>

        {{-- TOP BAR --}}
        <div class="flex items-center justify-between gap-3">

            {{-- SEARCH --}}
            <div class="relative w-[220px] sm:w-[260px] md:w-[300px]">

                <input type="text"
                id="searchInput"
                placeholder="Cari file di trash..."
                class="w-full h-11 rounded-2xl
                    border border-gray-200 dark:border-gray-700
                    bg-white dark:bg-gray-900
                    px-4 pr-10 text-sm
                    outline-none
                    focus:ring-2 focus:ring-red-500/30">

                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                    🔍
                </div>

            </div>

            {{-- RIGHT AREA --}}
            <div class="relative h-11 shrink-0">

                {{-- VIEW MODE --}}
                <div id="viewModeBar"
                class="flex items-center gap-2 transition duration-200">

                    <button id="listBtn"
                    onclick="setViewMode('list')"
                    class="view-btn h-11 px-4 rounded-xl border
                        border-gray-200 dark:border-gray-700
                        bg-white dark:bg-gray-900 text-sm
                        transition-all duration-200">

                        ☰ List

                    </button>

                    <button id="gridBtn"
                    onclick="setViewMode('grid')"
                    class="view-btn h-11 px-4 rounded-xl border
                        border-gray-200 dark:border-gray-700
                        bg-white dark:bg-gray-900 text-sm
                        transition-all duration-200">

                        ⊞ Grid

                    </button>

                </div>

                {{-- BULK BAR --}}
                <div id="bulkBar"
                class="hidden absolute inset-0 z-20
                    items-center justify-between
                    h-11 pl-1 pr-1.5
                    rounded-xl
                    border border-gray-200 dark:border-gray-700
                    bg-white dark:bg-[#202124]
                    shadow-sm dark:text-gray-700">

                    {{-- LEFT --}}
                    <div class="flex items-center min-w-0">

                        <button onclick="clearSelection()"
                        class="w-9 h-9 rounded-full
                               hover:bg-gray-100 dark:hover:bg-white/10
                               flex items-center justify-center
                               text-lg transition">

                            ✕

                        </button>

                        <div class="ml-1 text-sm font-medium whitespace-nowrap">
                            <span id="selectedCount">0</span> dipilih
                        </div>

                    </div>

                    {{-- RIGHT --}}
                    <div class="relative">

                        <button id="bulkMenuBtn"
                        onclick="toggleBulkMenu()"
                        class="w-9 h-9 rounded-full
                               hover:bg-gray-100 dark:hover:bg-white/10
                               flex items-center justify-center
                               text-xl transition">

                            ⋮

                        </button>

                        {{-- DROPDOWN --}}
                        <div id="bulkDropdown"
                        class="hidden absolute right-0 top-11 z-50
                               w-52 overflow-hidden
                               rounded-2xl
                               border border-gray-200 dark:border-gray-700
                               bg-white dark:bg-[#2b2c2f]
                               shadow-xl">

                            <button onclick="bulkRestore()"
                            class="w-full px-4 py-3
                                   flex items-center gap-3
                                   text-sm
                                   hover:bg-gray-100 dark:hover:bg-white/10
                                   transition">

                                <span class="text-lg">↩</span>
                                Restore

                            </button>

                            <button onclick="bulkDelete()"
                            class="w-full px-4 py-3
                                   flex items-center gap-3
                                   text-sm text-red-600 dark:text-red-400
                                   hover:bg-red-50 dark:hover:bg-red-500/10
                                   transition">

                                <span class="text-lg">🗑</span>
                                Hapus Permanen

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- BODY --}}
<div class="flex-1 overflow-hidden">

        {{-- EMPTY --}}
        @if($files->count() == 0)

        <div class="h-full flex flex-col items-center justify-center text-center">

            <div class="text-8xl opacity-60 mb-5">
                🗑
            </div>

            <h2 class="text-2xl font-bold mb-2">
                Trash Kosong
            </h2>

            <p class="text-gray-500 dark:text-gray-400 max-w-md">
                File yang dihapus akan muncul di sini sebelum dihapus permanen.
            </p>

        </div>

        @else

        {{-- SCROLL AREA --}}
        <div id="trashContainer"
        class="h-full overflow-y-auto overflow-x-hidden">

            {{-- LIST VIEW --}}
            <div id="listView"
            class="relative">

                {{-- spacer --}}
                <div id="virtualSpacer"></div>

                {{-- rendered items --}}
                <div id="virtualItems"
                class="absolute top-0 left-0 w-full"></div>

            </div>

            <script id="trashData" type="application/json">
            [
            @foreach($files as $f)
            {
                "id": {{ $f->id }},
                "name": @json($f->name),
                "type": @json($f->type),
                "deleted": @json($f->deleted_at?->diffForHumans()),
                "remaining": "{{ max(0, ceil(30 - $f->deleted_at->diffInDays(now(), false))) }}",
                "size": "{{ $f->type === 'file'
                    ? number_format(
                        Storage::disk('public')->exists($f->path)
                        ? Storage::disk('public')->size($f->path)/1024
                        : 0,1)
                    : '' }}"
            }
            @if(!$loop->last),@endif
            @endforeach
            ]
            </script>

            {{-- GRID VIEW --}}
            <div id="gridView"
            class="hidden grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 pb-0">

                @foreach($files as $f)

                <div class="trash-item group
                            rounded-3xl p-4
                            border border-gray-200 dark:border-gray-800
                            bg-white dark:bg-gray-900
                            hover:border-red-300 dark:hover:border-red-700
                            hover:shadow-md transition"

                    data-name="{{ strtolower($f->name) }}">

                    <div class="flex items-start justify-between mb-4">

                        <input type="checkbox"
                        class="select-item w-4 h-4 rounded"
                        value="{{ $f->id }}">

                        <div class="text-4xl">
                            @if($f->type === 'folder')
                                📁
                            @else
                                📄
                            @endif
                        </div>

                    </div>

                    <div class="font-medium truncate mb-2">
                        {{ $f->name }}
                    </div>

                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4">

                        Dihapus {{ $f->deleted_at?->diffForHumans() }}
                        •
                        Auto dihapus
                        {{ max(0, ceil(30 - $f->deleted_at->diffInDays(now(), false))) }} hari lagi

                    </div>

                </div>

                @endforeach

            </div>

        </div>

        @endif

    </div>

</div>

<div id="confirmModal"
     class="hidden fixed inset-0 z-[99999]
            bg-black/40 backdrop-blur-sm
            flex items-center justify-center">

    <div class="w-[90%] max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-xl p-5">

        <h2 class="text-lg font-semibold mb-2">
            Konfirmasi
        </h2>

        <p id="confirmMessage"
           class="text-sm text-gray-600 dark:text-gray-300 mb-5">
        </p>

        <div class="flex justify-end gap-2">

            <button onclick="closeConfirm()"
                    class="px-4 py-2 rounded-xl text-sm
                           bg-gray-100 dark:bg-gray-800 hover:opacity-80">
                Batal
            </button>

            <button id="confirmOkBtn"
                    class="px-4 py-2 rounded-xl text-sm
                           bg-red-600 text-white hover:bg-red-700">
                Hapus
            </button>

        </div>
    </div>
</div>

<style>
html,
body{
    height:100%;
    overflow:hidden;
}

#trashContainer{
    scrollbar-width:none;
    -ms-overflow-style:none;
}

#trashContainer::-webkit-scrollbar{
    display:none;
}

/* penting */
#listView,
#gridView{
    padding-bottom:0 !important;
    margin-bottom:0 !important;
}

</style>

<script>

const selected = new Set();

const searchInput = document.getElementById('searchInput');

const bulkBar = document.getElementById('bulkBar');

const selectedCount = document.getElementById('selectedCount');

const listView = document.getElementById('listView');

const gridView = document.getElementById('gridView');

const csrf =
document.querySelector('meta[name="csrf-token"]').content;

/* =========================
   TOAST
========================= */

function showToast(message, type='success'){

    const toast = document.createElement('div');

    toast.className = `
        fixed bottom-5 right-5 z-[9999]
        max-w-sm w-auto
        px-4 py-3 rounded-2xl shadow-lg
        text-white text-sm font-medium
        transition-all duration-300
        translate-y-3 opacity-0
        ${type === 'success'
            ? 'bg-green-600'
            : 'bg-red-600'}
    `;

    toast.innerHTML = message;

    document.body.appendChild(toast);

    requestAnimationFrame(()=>{
        toast.classList.remove('translate-y-3','opacity-0');
    });

    setTimeout(()=>{
        toast.classList.add('translate-y-3','opacity-0');

        setTimeout(()=>{
            toast.remove();
        },300);

    },3000);
}

/* =========================
   VIEW MODE
========================= */

const listBtn = document.getElementById('listBtn');
const gridBtn = document.getElementById('gridBtn');

function setViewMode(mode){

    localStorage.setItem('trash_view', mode);

    if(!listView || !gridView){
        return;
    }

    // RESET SEMUA TOMBOL
    [listBtn, gridBtn].forEach(btn=>{

        btn.classList.remove(
            'bg-red-600',
            'text-white',
            'border-red-600',
            'shadow-md',
            'scale-[1.02]',
            'dark:bg-red-600'
        );

        // mode non aktif
        btn.classList.add(
            'bg-white',
            'dark:bg-gray-900',
            'text-gray-700',
            'dark:text-gray-200'
        );
    });

    if(mode === 'grid'){

        listView.classList.add('hidden');
        gridView.classList.remove('hidden');

        // ACTIVE GRID
        gridBtn.classList.remove(
            'bg-white',
            'dark:bg-gray-900',
            'text-gray-700',
            'dark:text-gray-200'
        );

        gridBtn.classList.add(
            'bg-blue-600',
            'dark:bg-blue-600',
            'text-white',
            'border-blue-300',
            'shadow-md',
            'scale-[1.02]'
        );

        gridBtn.innerHTML = '⊞ Grid';

    }else{

        gridView.classList.add('hidden');
        listView.classList.remove('hidden');

        renderVirtualList();

        // ACTIVE LIST
        listBtn.classList.remove(
            'bg-white',
            'dark:bg-gray-900',
            'text-gray-700',
            'dark:text-gray-200'
        );

        listBtn.classList.add(
            'bg-blue-600',
            'dark:bg-blue-600',
            'text-white',
            'border-blue-300',
            'shadow-md',
            'scale-[1.02]'
        );

        listBtn.innerHTML = '☰ List';
    }

    // reset label tombol non aktif
    if(mode !== 'list'){
        listBtn.innerHTML = '☰ List';
    }

    if(mode !== 'grid'){
        gridBtn.innerHTML = '⊞ Grid';
    }
}

function updateBulkBar(){

    if(selected.size){

        bulkBar.classList.remove('hidden');
        bulkBar.classList.add('flex');

    }else{

        bulkBar.classList.add('hidden');
        bulkBar.classList.remove('flex');

        bulkDropdown?.classList.add('hidden');
    }

    selectedCount.innerHTML = selected.size;
}

/* =========================
   REMOVE DOM
========================= */

function removeItem(button){

    const item = button?.closest('.trash-item');

    if(item){
        item.style.opacity = '0';
        item.style.transform = 'scale(.95)';

        setTimeout(()=>{
            item.remove();

            if(!document.querySelector('.trash-item')){
                location.reload();
            }
        },200);
    }else{
        location.reload();
    }
}

/* =========================
   ACTIONS
========================= */

async function restore(id, button){

    const res = await fetch(`/file-manager/restore/${id}`,{
        method:'POST',
        headers:{
            'X-CSRF-TOKEN': csrf
        }
    });

    if(res.ok){

        removeItem(button);

        showToast('File berhasil direstore');
    }
}

async function forceDelete(id, button){

    openConfirm('Hapus permanen file ini?', async ()=>{

        const res = await fetch(`/file-manager/force-delete/${id}`,{
            method:'DELETE',
            headers:{
                'X-CSRF-TOKEN': csrf
            }
        });

        if(res.ok){
            removeItem(button);
            showToast('File berhasil dihapus');
        }
    });
}

/* =========================
   BULK
========================= */

async function bulkRestore(){

    for(const id of selected){

        await fetch(`/file-manager/restore/${id}`,{
            method:'POST',
            headers:{
                'X-CSRF-TOKEN': csrf
            }
        });
    }

    showToast('Item berhasil direstore');

    setTimeout(()=>{
        location.reload();
    },400);
}

async function bulkDelete(){

    openConfirm('Hapus permanen semua item yang dipilih?', async ()=>{

        for(const id of selected){

            await fetch(`/file-manager/force-delete/${id}`,{
                method:'DELETE',
                headers:{
                    'X-CSRF-TOKEN': csrf
                }
            });
        }

        showToast('Item berhasil dihapus');

        setTimeout(()=>{
            location.reload();
        },400);
    });
}

let confirmCallback = null;

function openConfirm(message, onConfirm){

    document.getElementById('confirmMessage').innerText = message;

    const modal = document.getElementById('confirmModal');

    modal.classList.remove('hidden');

    confirmCallback = onConfirm;
}

function closeConfirm(){
    document.getElementById('confirmModal').classList.add('hidden');
    confirmCallback = null;
}

document.getElementById('confirmOkBtn').addEventListener('click', ()=>{
    if(typeof confirmCallback === 'function'){
        confirmCallback();
    }
    closeConfirm();
});

/* =========================
   BULK MENU
========================= */

const bulkDropdown =
document.getElementById('bulkDropdown');

function toggleBulkMenu(){

    bulkDropdown.classList.toggle('hidden');
}

document.addEventListener('click', e=>{

    const menu =
    document.getElementById('bulkMenuBtn');

    if(
        !menu?.contains(e.target) &&
        !bulkDropdown?.contains(e.target)
    ){
        bulkDropdown?.classList.add('hidden');
    }
});

function clearSelection(){

    selected.clear();

    document.querySelectorAll('.select-item')
    .forEach(cb=> cb.checked = false);

    updateBulkBar();
}


/* =========================
   CTRL + A
========================= */

document.addEventListener('keydown', e=>{

    if(
        (e.ctrlKey || e.metaKey) &&
        e.key.toLowerCase() === 'a'
    ){

        const active = document.activeElement;

        // izinkan Ctrl+A di input selain search
        if(
            active &&
            (
                active.tagName === 'TEXTAREA' ||
                active.isContentEditable
            )
        ){
            return;
        }

        // kalau focus di search dan ada text → normal select text
        if(
            active?.id === 'searchInput' &&
            active.value.length > 0
        ){
            return;
        }

        e.preventDefault();

        const checkboxes = [
            ...document.querySelectorAll('.select-item')
        ];

        const allSelected =
            selected.size === checkboxes.length;

        if(allSelected){

            selected.clear();

            checkboxes.forEach(cb=>{
                cb.checked = false;
            });

        }else{

            checkboxes.forEach(cb=>{

                // skip item hidden hasil search
                const item = cb.closest('.trash-item');

                if(item?.style.display === 'none'){
                    return;
                }

                cb.checked = true;

                selected.add(cb.value);
            });
        }

        updateBulkBar();
    }
});

/* =========================
   VIRTUALIZED LIST
========================= */

const trashContainer =
document.getElementById('trashContainer');

const virtualItems =
document.getElementById('virtualItems');

const virtualSpacer =
document.getElementById('virtualSpacer');

const ITEM_HEIGHT = 92;
const BUFFER = 8;

let trashData = JSON.parse(
    document.getElementById('trashData').textContent
);

let filteredData = [...trashData];

function renderVirtualList(){

    if(
        !trashContainer ||
        !virtualItems ||
        !virtualSpacer ||
        listView.classList.contains('hidden')
    ){
        return;
    }

    const scrollTop =
    trashContainer.scrollTop;

    const containerHeight =
    trashContainer.clientHeight;

    const start =
    Math.max(
        0,
        Math.floor(scrollTop / ITEM_HEIGHT) - BUFFER
    );

    const visibleCount =
    Math.ceil(containerHeight / ITEM_HEIGHT)
    + BUFFER * 2;

    const end =
    Math.min(
        filteredData.length,
        start + visibleCount
    );

    virtualSpacer.style.height =
        `${filteredData.length * ITEM_HEIGHT}px`;

    virtualItems.style.transform =
        `translateY(${start * ITEM_HEIGHT}px)`;

    let html = '';

    for(let i=start; i<end; i++){

        const f = filteredData[i];

        html += `
        <div class="trash-item group
                    flex items-center justify-between gap-3
                    p-3 mb-2 rounded-2xl
                    border border-gray-200 dark:border-gray-800
                    bg-white dark:bg-gray-900
                    hover:border-red-300 dark:hover:border-red-700
                    hover:shadow-sm transition"
            data-name="${f.name.toLowerCase()}">

            <div class="flex items-center gap-3 min-w-0">

                <input type="checkbox"
                class="select-item w-4 h-4 rounded"
                value="${f.id}"
                ${selected.has(String(f.id))
                    ? 'checked'
                    : ''}>

                <div class="w-12 h-12 rounded-2xl
                            bg-red-100 dark:bg-red-900/20
                            flex items-center justify-center
                            text-2xl shrink-0">

                    ${f.type === 'folder'
                        ? '📁'
                        : '📄'}

                </div>

                <div class="min-w-0">

                    <div class="font-medium truncate">
                        ${f.name}
                    </div>

                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap items-center gap-2">

                        <span>
                            Dihapus ${f.deleted}
                            •
                            Auto dihapus ${f.remaining} hari lagi
                        </span>

                        ${f.type === 'file'
                            ? `
                            <span>•</span>
                            <span>${f.size} KB</span>
                            `
                            : ''}

                    </div>

                </div>

            </div>
        </div>
        `;
    }

    virtualItems.innerHTML = html;

    bindVirtualCheckboxes();
}

function bindVirtualCheckboxes(){

    const checkboxes =
    [...virtualItems.querySelectorAll('.select-item')];

    checkboxes.forEach((cb,index)=>{

        cb.addEventListener('change', ()=>{

            if(cb.checked){
                selected.add(cb.value);
            }else{
                selected.delete(cb.value);
            }

            updateBulkBar();
        });
    });
}

document.querySelectorAll('#gridView .select-item')
.forEach(cb=>{

    cb.addEventListener('change', ()=>{

        if(cb.checked){
            selected.add(cb.value);
        }else{
            selected.delete(cb.value);
        }

        updateBulkBar();
    });
});

/* =========================
   SEARCH VIRTUAL
========================= */

searchInput?.addEventListener('input', e=>{

    const q =
    e.target.value
    .trim()
    .toLowerCase();

    filteredData =
    trashData.filter(f=>
        f.name.toLowerCase().includes(q)
    );

    trashContainer.scrollTop = 0;

    renderVirtualList();
});

/* =========================
   SCROLL
========================= */

let virtualTicking = false;

trashContainer?.addEventListener('scroll', ()=>{

    if(virtualTicking) return;

    requestAnimationFrame(()=>{

        renderVirtualList();

        virtualTicking = false;

    });

    virtualTicking = true;
});

/* =========================
   VIEW MODE PATCH
========================= */

const originalSetViewMode =
setViewMode;

setViewMode = function(mode){

    originalSetViewMode(mode);

    if(mode === 'list'){
        renderVirtualList();
    }
};

/* =========================
   INIT
========================= */
setViewMode(
    localStorage.getItem('trash_view') || 'list'
);

</script>

@endsection