import './bootstrap';


import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

import 'flowbite';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

// Definisikan komponen dropdown dengan filter
Alpine.data('dropdown', (itemsKey, selectedKey, callback) => ({
    open: false,
    search: '',

    get items() {
        return this.$parent[itemsKey] || [];
    },

    get selected() {
        return this.$parent[selectedKey] || '';
    },
    set selected(val) {
        this.$parent[selectedKey] = val;
    },

    get filteredItems() {
        const q = this.search.toLowerCase().trim();
        return this.items.filter(item =>
            item.name.toLowerCase().includes(q)
        );
    },

    init() {
        if (this.selected) {
            const found = this.items.find(i => i.code == this.selected);
            if (found) this.search = found.name;
        }
    },

    selectItem(item) {
        this.selected = item.code;
        this.search = item.name;
        this.open = false;
        if (callback) callback(item.code);
    },

    clear() {
        this.selected = '';
        this.search = '';
        if (callback) callback('');
    }
}));

// ===== MODAL COMPONENT =====
Alpine.data('anggotaModal', () => ({
    modalOpen: false,
    selectedAnggota: {
        nama: '',
        departemen: '',
        jabatan: '',
        umur: '',
        pendidikan: '',
        mulaiBekerja: '',
        foto: null,
        jabatanPuk: ''
    },
    
    openModal(data) {
        this.selectedAnggota = data;
        this.modalOpen = true;
        document.body.style.overflow = 'hidden';
    },
    
    closeModal() {
        this.modalOpen = false;
        document.body.style.overflow = '';
    }
}));

// Buat fungsi global yang bisa diakses dari HTML
window.openModal = function(data) {
    // Cari instance Alpine di modal component
    const modalEl = document.querySelector('[x-data="anggotaModal"]');
    if (modalEl && modalEl._x_dataStack) {
        const dataStack = modalEl._x_dataStack;
        for (let i = dataStack.length - 1; i >= 0; i--) {
            if (dataStack[i].openModal && typeof dataStack[i].openModal === 'function') {
                dataStack[i].openModal(data);
                return;
            }
        }
    }
    console.warn('Modal component not found');
};

window.Alpine = Alpine;
Alpine.start();


import * as XLSX from 'xlsx';
window.XLSX = XLSX;

import Swal from 'sweetalert2';
window.Swal = Swal;

import Chart from 'chart.js/auto';
window.Chart = Chart;

import ChartDataLabels from 'chartjs-plugin-datalabels';
Chart.register(ChartDataLabels);

import $ from 'jquery';
window.$ = $;
window.jQuery = $;

import 'datatables.net-bs5';
import 'datatables.net-buttons-bs5';
import 'datatables.net-buttons/js/buttons.html5';

import JSZip from 'jszip';
window.JSZip = JSZip;

import '@tabler/icons-webfont/dist/tabler-icons.min.css';