<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KodeSuratController;
use App\Http\Controllers\NotulensiController;
use App\Http\Controllers\LaporanBendaharaController;
use App\Http\Controllers\LaporanBulananController;
use App\Http\Controllers\LaporanBelanjaController;
use App\Http\Controllers\BelanjaKoperasiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\FileManagerController; 
use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\AnggotaDashboardController;
use App\Http\Controllers\InventarisController;
use App\Http\Controllers\InventarisPeminjamanController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\Public\BeritaController;
use App\Http\Controllers\Public\KontakController;
use App\Http\Controllers\Public\LandingPageController;
use App\Http\Controllers\Public\AnggotaAuthController;
use App\Http\Controllers\Public\Admin\GalleryAdminController;
use App\Http\Controllers\Public\Admin\NewsAdminController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\Public\Admin\ProfileController;
use App\Http\Controllers\Public\Admin\StrukturController;
use App\Http\Controllers\Public\Admin\RolePermissionController;
use App\Http\Controllers\Public\Admin\AdminSocialMediaController;
use App\Http\Controllers\Public\GaleriController;
use App\Http\Controllers\Public\Admin\InformationAdminController;
use App\Http\Controllers\Public\InformationController;
use App\Http\Controllers\Public\Admin\AdminPageController;
use App\Http\Controllers\Public\AnggotaPasswordResetController;
use App\Http\Controllers\Public\LandingKeuanganController;
use App\Http\Controllers\Public\AnggotaProfilController;
use App\Http\Controllers\Public\Admin\PesanMasukController;
use App\Http\Controllers\Public\Admin\AdminKegiatanController;
use App\Http\Controllers\Public\Admin\AdminKegiatanPendaftaranController;
use App\Http\Controllers\Public\KegiatanController;
use App\Http\Controllers\AnggotaRfidPairingController;
use App\Http\Controllers\Public\Admin\SesiAbsensiController;
use App\Http\Controllers\InsentifController;
use App\Http\Controllers\KasSettingController;
use App\Http\Controllers\BidangSettingController;




Route::prefix('landing/admin')->name('admin.')->middleware('anggota.auth', 'check.page.access') ->group(function () {

    Route::get('/welcome', function () {
        return view('landing-page.admin.welcome');
    })->name('welcome');

    Route::get('pesan-masuk', [PesanMasukController::class, 'index'])->name('pesan-masuk.index');
    Route::patch('pesan-masuk/{contact}/baca', [PesanMasukController::class, 'markAsRead'])->name('pesan-masuk.baca');
    Route::delete('pesan-masuk/{contact}', [PesanMasukController::class, 'destroy'])->name('pesan-masuk.destroy');
    
    Route::prefix('sesi-absensi')->name('sesi-absensi.')->group(function () {
        Route::get('/', [SesiAbsensiController::class, 'index'])->name('index');
        Route::get('/create', [SesiAbsensiController::class, 'create'])->name('create');
        Route::post('/', [SesiAbsensiController::class, 'store'])->name('store');
        Route::get('/{sesi}', [SesiAbsensiController::class, 'show'])->name('show');
        Route::get('/{sesi}/edit', [SesiAbsensiController::class, 'edit'])->name('edit');
        Route::put('/{sesi}', [SesiAbsensiController::class, 'update'])->name('update');
        Route::delete('/{sesi}', [SesiAbsensiController::class, 'destroy'])->name('destroy');
        Route::post('/{sesi}/scan', [SesiAbsensiController::class, 'scan'])->name('scan');
        Route::get('/{sesi}/entries', [SesiAbsensiController::class, 'entries'])->name('entries');
        Route::post('/{sesi}/tutup', [SesiAbsensiController::class, 'tutup'])->name('tutup');
        Route::get('/{sesi}/export', [SesiAbsensiController::class, 'export'])->name('export');
        Route::post('/{sesi}/buka', [SesiAbsensiController::class, 'buka'])->name('buka');
        Route::delete('/{sesi}/entri/{entri}', [SesiAbsensiController::class, 'batalHadir'])->whereNumber('entri')->name('batal-hadir');
    });    

    // ===== Berita =====
    Route::get('/berita', [NewsAdminController::class, 'index'])->name('berita.index');
    Route::get('/berita/create', [NewsAdminController::class, 'create'])->name('berita.create');
    Route::post('/berita', [NewsAdminController::class, 'store'])->name('berita.store');
    Route::get('/berita/{berita}/edit', [NewsAdminController::class, 'edit'])->name('berita.edit');
    Route::put('/berita/{berita}', [NewsAdminController::class, 'update'])->name('berita.update');
    Route::delete('/berita/{berita}', [NewsAdminController::class, 'destroy'])->name('berita.destroy');
    Route::post('/berita/{berita}/submit', [NewsAdminController::class, 'submit'])->name('berita.submit');
    Route::post('/berita/{berita}/review', [NewsAdminController::class, 'review'])->name('berita.review');
    Route::post('/berita/{berita}/review-with-edit', [NewsAdminController::class, 'reviewWithEdit'])->name('berita.review-with-edit');
    Route::post('/berita/{berita}/approve', [NewsAdminController::class, 'approve'])->name('berita.approve');
    Route::post('/berita/{berita}/reject', [NewsAdminController::class, 'reject'])->name('berita.reject');
    Route::post('/berita/{berita}/takedown', [NewsAdminController::class, 'takedown'])->name('berita.takedown'); 
    Route::post('/berita/{berita}/restore', [NewsAdminController::class, 'restore'])->name('berita.restore'); 
    Route::post('/berita/bulk-action', [NewsAdminController::class, 'bulkAction'])->name('berita.bulk-action');
    Route::post('/berita/{berita}/schedule', [NewsAdminController::class, 'schedulePublish'])->name('berita.schedule');
    Route::get('/berita/{berita}/preview', [NewsAdminController::class, 'preview'])->name('berita.preview');
    Route::get('/media/list', [NewsAdminController::class, 'getMediaList'])->name('media.list');
    Route::post('/media/upload', [NewsAdminController::class, 'uploadMedia'])->name('media.upload');
    Route::post('/berita/auto-save', [NewsAdminController::class, 'autoSave'])->name('berita.auto-save');
    Route::post('/berita/newsletter/{id}', [NewsAdminController::class, 'sendNewsletter'])->name('berita.newsletter');
   

    // ===== Galeri =====
    Route::get('/galeri', [GalleryAdminController::class, 'index'])->name('galeri.index');
    Route::get('/galeri/preview', [GalleryAdminController::class, 'preview'])->name('admin.galeri.preview');
    Route::get('/galeri/create', [GalleryAdminController::class, 'create'])->name('galeri.create');
    Route::post('/galeri', [GalleryAdminController::class, 'store'])->name('galeri.store');
    Route::post('galeri/folder', [GalleryAdminController::class, 'storeFolder'])->name('galeri.store-folder');
    Route::post('galeri/album/aksi', [GalleryAdminController::class, 'albumAksi'])->name('galeri.album-aksi');
    Route::delete('/galeri/{foto}', [GalleryAdminController::class, 'destroy'])->name('galeri.destroy');
    Route::post('/galeri/{foto}/submit', [GalleryAdminController::class, 'submit'])->name('galeri.submit');
    Route::post('/galeri/{foto}/review-bidang5', [GalleryAdminController::class, 'reviewBidang5'])->name('galeri.review-bidang5');
    Route::post('/galeri/{foto}/approve-sekretaris', [GalleryAdminController::class, 'approveSekretaris'])->name('galeri.approve-sekretaris');
    Route::get('galeri/{foto}/edit', [GalleryAdminController::class, 'edit'])->name('galeri.edit');
    Route::put('galeri/{foto}', [GalleryAdminController::class, 'update'])->name('galeri.update');
    Route::get('galeri-kegiatan', [GalleryAdminController::class, 'kegiatan'])->name('galeri.kegiatan');
    Route::post('galeri-kegiatan/rename', [GalleryAdminController::class, 'renameKegiatan'])->name('galeri.rename-kegiatan');
    Route::get('galeri-massal', [GalleryAdminController::class, 'bulk'])->name('galeri.bulk');
    Route::post('galeri-massal', [GalleryAdminController::class, 'bulkStore'])->name('galeri.bulk-store');

    Route::prefix('kegiatan')->name('kegiatan.')->group(function () { 
        Route::get('/', [AdminKegiatanController::class, 'index'])->name('index');
        Route::get('/create', [AdminKegiatanController::class, 'create'])->name('create');
        Route::post('/', [AdminKegiatanController::class, 'store'])->name('store');
        Route::get('/{kegiatan}/edit', [AdminKegiatanController::class, 'edit'])->name('edit');
        Route::put('/{kegiatan}', [AdminKegiatanController::class, 'update'])->name('update');
        Route::delete('/{kegiatan}', [AdminKegiatanController::class, 'destroy'])->name('destroy');
        Route::patch('/{kegiatan}/tutup', [AdminKegiatanController::class, 'tutup'])->name('tutup');
        Route::patch('/{kegiatan}/buka', [AdminKegiatanController::class, 'bukaKembali'])->name('buka');

        Route::prefix('{kegiatan}/pendaftar')->name('pendaftar.')->group(function () {
            Route::get('/', [AdminKegiatanPendaftaranController::class, 'index'])->name('index');
            Route::get('/export', [AdminKegiatanPendaftaranController::class, 'exportExcel'])->name('export');
            Route::delete('/{pendaftaran}', [AdminKegiatanPendaftaranController::class, 'destroy'])->name('destroy');
            Route::get('/cari-anggota', [AdminKegiatanPendaftaranController::class, 'cariAnggota'])->name('cari-anggota');
            Route::post('/', [AdminKegiatanPendaftaranController::class, 'storeManual'])->name('store');
            Route::get('/scan', [AdminKegiatanPendaftaranController::class, 'scanPage'])->name('scan-page');
            Route::post('/scan', [AdminKegiatanPendaftaranController::class, 'scan'])->name('scan');
            Route::get('/hadir-list', [AdminKegiatanPendaftaranController::class, 'daftarHadirJson'])->name('hadir-list');
            Route::delete('/{pendaftaran}/hadir', [AdminKegiatanPendaftaranController::class, 'batalHadir'])->name('batal-hadir');
        });
    });

    // Di dalam group admin yang sudah ada (middleware web + anggota.auth)
    
    Route::get('/informasi', [InformationAdminController::class, 'index'])->name('informasi.index');
    Route::get('/informasi/create', [InformationAdminController::class, 'create'])->name('informasi.create');
    Route::post('/informasi', [InformationAdminController::class, 'store'])->name('informasi.store');
    Route::get('/informasi/{informasi}/edit', [InformationAdminController::class, 'edit'])->name('informasi.edit');
    Route::put('/informasi/{informasi}', [InformationAdminController::class, 'update'])->name('informasi.update');
    Route::delete('/informasi/{informasi}', [InformationAdminController::class, 'destroy'])->name('informasi.destroy');

    // ===== Medsos =====
    Route::get('/medsos/{id}/edit', [AdminSocialMediaController::class, 'edit'])->name('medsos.edit');
    Route::resource('/medsos', AdminSocialMediaController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    // ===== Profile & Struktur (hanya Sekretaris) — tetap pakai middleware =====
    Route::middleware('anggota.role:Sekretaris')->group(function () {
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::prefix('struktur')->name('struktur.')->group(function () {
            Route::get('/', [StrukturController::class, 'index'])->name('index');
            Route::get('/create', [StrukturController::class, 'create'])->name('create');
            Route::post('/', [StrukturController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [StrukturController::class, 'edit'])->name('edit');
            Route::put('/{id}', [StrukturController::class, 'update'])->name('update');
            Route::delete('/{id}', [StrukturController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/move-up', [StrukturController::class, 'moveUp'])->name('move-up');
            Route::post('/{id}/move-down', [StrukturController::class, 'moveDown'])->name('move-down');
            Route::get('/anggota-by-nik', [StrukturController::class, 'anggotaByNik'])->name('anggota-by-nik');
            Route::get('/anggota-by-id', [StrukturController::class, 'anggotaById'])->name('anggota-by-id');
        });
    });

    // ===== Role Permission (hanya Sekretaris) =====
    Route::middleware('anggota.role:Sekretaris')->group(function () {
        Route::get('/role-permission', [RolePermissionController::class, 'index'])->name('role-permission.index');
        Route::put('/role-permission', [RolePermissionController::class, 'update'])->name('role-permission.update');
    });

   // ===== Manajemen Halaman (Menu Sidebar) — hanya Sekretaris =====
    Route::middleware('anggota.role:Sekretaris')->prefix('halaman')->name('halaman.')->group(function () {
        Route::get('/', [AdminPageController::class, 'index'])->name('index');
        Route::get('/create', [AdminPageController::class, 'create'])->name('create');
        Route::post('/', [AdminPageController::class, 'store'])->name('store');
        Route::get('/{adminPage}/edit', [AdminPageController::class, 'edit'])->name('edit');
        Route::put('/{adminPage}', [AdminPageController::class, 'update'])->name('update');
        Route::delete('/{adminPage}', [AdminPageController::class, 'destroy'])->name('destroy');
        Route::post('/{adminPage}/move-up', [AdminPageController::class, 'moveUp'])->name('move-up');
        Route::post('/{adminPage}/move-down', [AdminPageController::class, 'moveDown'])->name('move-down');
        Route::get('/p/{page:slug}', [AdminPageController::class, 'show'])->name('show');
    });

});


Route::get('/', [LandingPageController::class, 'index'])->name('landing.index');
Route::get('/berita', [BeritaController::class, 'index'])->name('berita.index');
Route::get('/berita/{slug}', [BeritaController::class, 'show'])->name('berita.show');
Route::post('/kontak', [KontakController::class, 'store'])->name('kontak.store');
Route::get('/galeri', [GaleriController::class, 'index'])->name('galeri.index');
Route::get('/informasi/{slug}', [InformationController::class, 'show'])->name('informasi.show');


Route::get('/login', [AnggotaAuthController::class, 'showLogin'])->name('anggota.login');
Route::post('/login', [AnggotaAuthController::class, 'login'])->name('anggota.login.process');
Route::post('/logout', [AnggotaAuthController::class, 'logout'])->name('anggota.logout');



Route::prefix('lupa-password')->name('anggota.password.')->group(function () {
    Route::get('/', [AnggotaPasswordResetController::class, 'showIdentityForm'])->name('request');
    Route::post('/', [AnggotaPasswordResetController::class, 'verifyIdentity'])->name('identity.check');

    Route::get('/otp', [AnggotaPasswordResetController::class, 'showOtpForm'])->name('otp');
    Route::post('/otp', [AnggotaPasswordResetController::class, 'verifyOtp'])->name('otp.check');
    Route::post('/otp/kirim-ulang', [AnggotaPasswordResetController::class, 'resendOtp'])->name('otp.resend');

    Route::get('/reset', [AnggotaPasswordResetController::class, 'showResetForm'])->name('reset');
    Route::post('/reset', [AnggotaPasswordResetController::class, 'resetPassword'])->name('update');
});


Route::middleware(['web', 'anggota.login'])
    ->prefix('anggota/keuangan')
    ->name('landing.keuangan.')
    ->group(function () {
        Route::get('/debit-kredit', [LandingKeuanganController::class, 'debitKredit'])
            ->name('debit-kredit');
 
        Route::get('/detail-jenis', [LandingKeuanganController::class, 'detailJenis'])
            ->name('detail-jenis');
 
        Route::get('/realisasi-bidang', [LandingKeuanganController::class, 'realisasiPerBidang'])
            ->name('realisasi-bidang');

        
    });

Route::middleware(['web', 'anggota.login'])->group(function () {    
        Route::get('/agenda-data', [LandingPageController::class, 'agendaData'])->name('landing.agenda.data');
        Route::get('/inventaris-data', [LandingPageController::class, 'inventarisData'])->name('landing.inventaris.data');
        Route::get('/anggota/profil', [AnggotaProfilController::class, 'show'])->name('anggota.profil');
        Route::get('/anggota/profil/edit', [AnggotaProfilController::class, 'edit'])->name('anggota.profil.edit');
        Route::put('/anggota/profil', [AnggotaProfilController::class, 'update'])->name('anggota.profil.update');
        Route::get('/profil/qrcode', [AnggotaProfilController::class, 'qrCode'])->name('anggota.profil.qrcode');
        Route::prefix('anggota/api/wilayah')->name('anggota.api.wilayah.')->group(function () {
            Route::get('/kota/{provinsiCode}', function (string $provinsiCode) {
                return \Laravolt\Indonesia\Models\City::where('province_code', $provinsiCode)
                    ->orderBy('name')
                    ->get(['code', 'name']);
            })->name('kota');

            Route::get('/kecamatan/{kotaCode}', function (string $kotaCode) {
                return \Laravolt\Indonesia\Models\District::where('city_code', $kotaCode)
                    ->orderBy('name')
                    ->get(['code', 'name']);
            })->name('kecamatan');

            Route::get('/kelurahan/{kecamatanCode}', function (string $kecamatanCode) {
                return \Laravolt\Indonesia\Models\Village::where('district_code', $kecamatanCode)
                    ->orderBy('name')
                    ->get(['code', 'name']);
            })->name('kelurahan');
        });

        Route::get('/kegiatan', [KegiatanController::class, 'index'])->name('kegiatan.index');
        Route::get('/kegiatan/{kegiatan}', [KegiatanController::class, 'show'])->name('kegiatan.show');
        Route::post('/kegiatan/{kegiatan}/daftar', [KegiatanController::class, 'daftar'])->name('kegiatan.daftar');

});      



// ===== Ganti password wajib (login pertama dengan password default) =====
Route::get('/anggota/ganti-password', [AnggotaAuthController::class, 'showGantiPassword'])
    ->name('anggota.password.ganti');

Route::post('/anggota/ganti-password', [AnggotaAuthController::class, 'updatePassword'])
    ->middleware('throttle:10,1')
    ->name('anggota.ganti-password.update');
/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/
Route::controller(AuthController::class)->group(function () {
    Route::get('/dashboard/login', 'showLogin')->name('login');
    Route::post('/dashboard/login', 'processLogin')->name('login.process');
    Route::get('/dashboard/logout', 'logout')->name('logout');
});

/*
|--------------------------------------------------------------------------
| ROOT REDIRECT
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (middleware: authcheck)
|--------------------------------------------------------------------------
*/
Route::middleware(['authcheck'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/tren-keuangan', [DashboardController::class, 'trenKeuanganApi'])->name('dashboard.tren-keuangan');
    Route::get('/dashboard/debit-kredit', [DashboardController::class, 'debitKreditApi'])->name('dashboard.debit-kredit');
    Route::get('/dashboard/detail-bidang', [DashboardController::class, 'detailBidangApi'])
    ->name('dashboard.detail-bidang');
    Route::get('/dashboard/detail-bidang', [DashboardController::class, 'detailBidangApi'])
    ->name('dashboard.detail-bidang');
    

    /*
    |--------------------------------------------------------------------------
    | 💰 PENGAJUAN DANA
    |--------------------------------------------------------------------------
    */
    Route::prefix('pengajuan')->name('pengajuan.')->group(function () {
        Route::get('/', [PengajuanController::class, 'index'])->name('index');
        Route::get('/data', [PengajuanController::class, 'data'])->name('data');
        Route::post('/store', [PengajuanController::class, 'store'])->name('store');
        Route::post('/delete', [PengajuanController::class, 'delete'])->name('delete');
         Route::post('/update',[PengajuanController::class, 'update'])->name('update');

        Route::post('/approve', [PengajuanController::class, 'approve'])->name('approve');       
        Route::post('/reject', [PengajuanController::class, 'reject'])->name('reject');
        Route::get('/export-excel', [PengajuanController::class, 'exportExcel'])->name('export');
        Route::post('/uploadLaporan', [PengajuanController::class, 'uploadLaporan'])->name('uploadLaporan');
        Route::post('/updateAktualBiaya', [PengajuanController::class, 'updateAktualBiaya'])->name('updateAktualBiaya');
	Route::post('/status-biaya',[PengajuanController::class, 'updateStatusBiaya'])->name('updateStatusBiaya');
	Route::post('/konfirmasi-pengembalian',[PengajuanController::class, 'konfirmasiPengembalian'])->name('konfirmasiPengembalian');
        Route::post('/konfirmasi-kurang',[PengajuanController::class, 'konfirmasiTransferKurang'])->name('konfirmasiTransferKurang');
        
    });
    Route::get('/file/preview/{type}/{id}', function ($type, $id) {
        $allowed = ['proposal', 'laporan'];
        abort_unless(in_array($type, $allowed), 404);
        $path = "private/file/{$type}/{$id}.pdf";
        abort_unless(Storage::exists($path), 404);
        return response()->file(
            Storage::path($path),
            ['Content-Type' => 'application/pdf']
        );
    })->name('file.preview');
    Route::get('/pengajuan/file/{type}/{id}', [PengajuanController::class, 'previewFile'])
    ->name('pengajuan.preview');


    // Laporan manual
    Route::get('/laporan/manual/{id}', [LaporanController::class, 'manual']);
    Route::post('/laporan/manual/store', [LaporanController::class, 'storeManual']);
    Route::post('/laporan/preview', [LaporanController::class, 'preview'])->name('laporan.preview');

    /*
    |--------------------------------------------------------------------------
    | 🧾 Agenda
    |--------------------------------------------------------------------------
    */
    Route::prefix('agenda')->name('agenda.')->group(function () {
        Route::get('/', [AgendaController::class, 'index'])->name('index');
        Route::get('/data', [AgendaController::class, 'data'])->name('data');
        Route::post('/', [AgendaController::class, 'store'])->name('store');
        Route::put('/{event}', [AgendaController::class, 'update'])->name('update');
        Route::delete('/{event}', [AgendaController::class, 'destroy'])->name('destroy');
        Route::patch('/{event}/done', [AgendaController::class, 'markDone'])->name('markDone');
        Route::get('/users-by-role', [AgendaController::class, 'getUsersByRole'])->name('users.by.role');
    });


    /*
    |--------------------------------------------------------------------------
    | 🧾 KODE PENOMORAN SURAT
    |--------------------------------------------------------------------------
    */
    Route::prefix('kode-surat')->name('kode-surat.')->group(function () {
        Route::get('/', [KodeSuratController::class, 'index'])->name('index');
        Route::post('/generate', [KodeSuratController::class, 'generate'])->name('generate');
        Route::post('/list', [KodeSuratController::class, 'list'])->name('list');
        Route::get('/data', [KodeSuratController::class, 'data'])->name('data');

    });

 
    Route::post('/profile/{username}', [UserController::class, 'updateProfile'])
    ->name('profile.update');

    




    /*
    |--------------------------------------------------------------------------
    | 👥 USER MANAGEMENT
    |--------------------------------------------------------------------------
    | Hanya bisa diakses oleh Ketua & Sekretaris
    |--------------------------------------------------------------------------
    */
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users/store', [UserController::class, 'store'])->name('users.store');
    Route::post('/users/update/{username}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/destroy/{username}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/profile/{username}', [UserController::class, 'updateProfile'])
        ->name('profile.update');
    Route::get('/users/anggota/{nik}', [UserController::class, 'getAnggotaByNik']);

    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    |            Laporan Belanja ke Koperasi
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    */
        Route::get('/laporan-belanja',[LaporanBelanjaController::class,'index'])->name('laporan-belanja.index');
         Route::post('/laporan-belanja/buat', [LaporanBelanjaController::class,'buat'])
         ->name('laporan-belanja.buat'); 

        Route::get('/belanja-koperasi',[BelanjaKoperasiController::class,'index'])
            ->name('belanja-koperasi.index');
        Route::post('/belanja-koperasi/simpan',[BelanjaKoperasiController::class,'simpan']);


    /*
    |--------------------------------------------------------------------------
    | 📊 Laporan Keuangan Bulanan
    |--------------------------------------------------------------------------
    | Hanya bisa diakses oleh Ketua & Sekretaris
    |--------------------------------------------------------------------------
    */
    Route::prefix('laporan-bendahara')->name('laporan-bendahara.')->group(function () {
        Route::get('/', [LaporanBendaharaController::class, 'index'])->name('index');
        Route::get('/pdf', [LaporanBendaharaController::class, 'pdf'])->name('pdf');
        Route::post('/store', [LaporanBendaharaController::class,'store'])->name('store');
        
        
    });

    Route::get('/laporan-bulanan', [LaporanBulananController::class,'index'])
         ->name('laporan-bulanan.index');
    Route::post('/laporan-bulanan/buat', [LaporanBulananController::class,'buat'])
         ->name('laporan-bulanan.buat'); 
    Route::post('/laporan-bendahara/approve-sekretaris', [LaporanBendaharaController::class, 'approveSekretaris'])
    ->name('laporan-bendahara.approve-sekretaris');
    Route::post('/laporan-bendahara/approve-ketua', [LaporanBendaharaController::class, 'approveKetua'])
    ->name('laporan-bendahara.approve-ketua');
    Route::post('/laporan-bendahara/submit-bendahara', [LaporanBendaharaController::class, 'submitBendahara'])
    ->name('laporan-bendahara.submit-bendahara');
    Route::post('laporan-bendahara/reject-sekretaris', [LaporanBendaharaController::class, 'rejectSekretaris'])
    ->name('laporan-bendahara.reject-sekretaris');
    Route::post('laporan-bendahara/reject-ketua', [LaporanBendaharaController::class, 'rejectKetua'])
        ->name('laporan-bendahara.reject-ketua');
    Route::post('/laporan-bendahara/reopen', [LaporanBendaharaController::class, 'reopen'])
    ->name('laporan-bendahara.reopen');

    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');

    Route::get('/pengaturan/insentif',  [InsentifController::class, 'index'])->name('insentif.index');
    Route::post('/pengaturan/insentif', [InsentifController::class, 'sync'])->name('insentif.sync');
    Route::get('/laporan-bendahara/insentif-master', [LaporanBendaharaController::class, 'insentifMaster'])
    ->name('laporan-bendahara.insentif-master');
    Route::get('/pengaturan/kas',[KasSettingController::class, 'index'])->name('kas.index');
    Route::post('/pengaturan/kas',[KasSettingController::class, 'sync'])->name('kas.sync');
    Route::post('/pengaturan/kas/validasi',[KasSettingController::class, 'validasi'])->name('kas.validasi');
    Route::post('/pengaturan/kas/simulasi',[KasSettingController::class, 'simulasi'])->name('kas.simulasi');
    Route::post('/laporan-bendahara/hitung', [LaporanBendaharaController::class, 'hitung'])
    ->name('laporan-bendahara.hitung');
    Route::post('/laporan-bendahara/muat-konfigurasi', [LaporanBendaharaController::class, 'muatKonfigurasi'])
    ->name('laporan-bendahara.muat-konfigurasi');

    Route::get('/pengaturan/bidang',[BidangSettingController::class, 'index'])->name('bidang.index');
    Route::post('/pengaturan/bidang',[BidangSettingController::class, 'sync'])->name('bidang.sync');
    Route::post('/pengaturan/bidang/validasi', [BidangSettingController::class, 'validasi'])->name('bidang.validasi');

    /*
    |---------------------------------------------------------------
    |
    |   File manager
    |
    |---------------------------------------------------------------
    */
    Route::prefix('file-manager')->group(function(){

        Route::get('/', [FileManagerController::class, 'index'])->name('filemanager.index');
        Route::get('/data', [FileManagerController::class, 'data']);
        Route::post('/folder', [FileManagerController::class, 'storeFolder']);
        Route::post('/upload', [FileManagerController::class, 'upload']);
        Route::delete('/delete/{id}', [FileManagerController::class, 'delete']);
        Route::post('/rename/{id}', [FileManagerController::class, 'rename']);
        Route::get('/trash', [FileManagerController::class, 'trash'])->name('filemanager.trash');
        Route::post('/restore/{id}', [FileManagerController::class, 'restore']);
        Route::get('/download/{id}', [FileManagerController::class, 'download']);
        Route::post('/move', [FileManagerController::class, 'move']);
        Route::delete('/force-delete/{id}',[FileManagerController::class, 'forceDelete']);
        Route::delete('/empty-trash',[FileManagerController::class, 'emptyTrash']);
        Route::get('/folders-tree', [FileManagerController::class, 'foldersTree']);
        Route::delete('/bulk-delete', [FileManagerController::class, 'bulkDelete']);
        Route::get('/file/{id}', [FileManagerController::class, 'view']);

    });


    /*
    |--------------------------------------------------------------------------
    | API WILAYAH — laravolt/indonesia
    | Endpoint AJAX untuk cascade dropdown provinsi → kota → kecamatan → kelurahan
    |--------------------------------------------------------------------------
    */

    Route::prefix('api/wilayah')->name('api.wilayah.')->group(function () {

        // Kota / Kabupaten berdasarkan kode provinsi
        Route::get('/kota/{provinsiCode}', function (string $provinsiCode) {
            return \Laravolt\Indonesia\Models\City::where('province_code', $provinsiCode)
                ->orderBy('name')
                ->get(['code', 'name']);
        })->name('kota');

        // Kecamatan berdasarkan kode kota
        Route::get('/kecamatan/{kotaCode}', function (string $kotaCode) {
            return \Laravolt\Indonesia\Models\District::where('city_code', $kotaCode)
                ->orderBy('name')
                ->get(['code', 'name']);
        })->name('kecamatan');

        // Kelurahan / Desa berdasarkan kode kecamatan
        Route::get('/kelurahan/{kecamatanCode}', function (string $kecamatanCode) {
            return \Laravolt\Indonesia\Models\Village::where('district_code', $kecamatanCode)
                ->orderBy('name')
                ->get(['code', 'name']);
        })->name('kelurahan');

    });


     /*
    |--------------------------------------------------------------------------
    | DASHBOARD ANGGOTA
    |--------------------------------------------------------------------------
    */

    Route::prefix('anggota/dashboard')->name('anggota.dashboard.')->group(function () {
 
    // Halaman utama dashboard
    Route::get('/', [AnggotaDashboardController::class, 'index'])
        ->name('index');
 
    // API endpoint — ganti tahun tren (dipanggil fetch JS, bukan form)
    Route::get('/tren', [AnggotaDashboardController::class, 'trenApi'])
        ->name('tren');
    });  

    Route::get('/anggota/rfid-pairing', [AnggotaRfidPairingController::class, 'index'])->name('anggota.rfid-pairing');
    Route::post('/anggota/rfid-pairing/check', [AnggotaRfidPairingController::class, 'check'])->name('anggota.rfid-pairing.check');
    Route::get('/anggota/rfid-pairing/search', [AnggotaRfidPairingController::class, 'search'])->name('anggota.rfid-pairing.search');
    Route::post('/anggota/rfid-pairing/assign', [AnggotaRfidPairingController::class, 'assign'])->name('anggota.rfid-pairing.assign');
    Route::get('/anggota/rfid-pairing/paired-list', [AnggotaRfidPairingController::class, 'pairedList'])->name('anggota.rfid-pairing.paired-list');
    

    Route::post('anggota/total-karyawan', [AnggotaController::class, 'saveTotalKaryawan'])
    ->name('anggota.total-karyawan.save');

    Route::get('anggota/dashboard/departemen',
    [AnggotaDashboardController::class, 'departemenApi'])
    ->name('anggota.dashboard.departemen');

    Route::get(
        '/anggota/dashboard/detail',
        [AnggotaDashboardController::class, 'detailApi']
    )->name('anggota.dashboard.detail');



    /*
    |--------------------------------------------------------------------------
    | IMPORT & EXPORT EXCEL ANGGOTA
    |--------------------------------------------------------------------------
    */

    Route::get('/anggota/import', [AnggotaController::class, 'importForm'])
        ->name('anggota.import.form');

    Route::post('/anggota/import/preview', [AnggotaController::class, 'importPreview'])
        ->name('anggota.import.preview');

    Route::post('/anggota/import/process', [AnggotaController::class, 'importProcess'])
        ->name('anggota.import.process');

    Route::get('/anggota/export', [AnggotaController::class, 'export'])->name('anggota.export');

    /*
    |--------------------------------------------------------------------------
    | RESOURCE ANGGOTA
    |--------------------------------------------------------------------------
    */

    Route::resource('anggota', AnggotaController::class)
        ->parameters(['anggota' => 'anggota']);

     /*
    |--------------------------------------------------------------------------
    | Inventaris
    |--------------------------------------------------------------------------
    | 
    |--------------------------------------------------------------------------
    */
    Route::prefix('inventaris')->group(function () {

    // ==========================================
    // INVENTARIS - AKSES UMUM
    // ==========================================

    Route::get('/', [InventarisController::class, 'index'])
        ->name('inventaris.index');


    // ==========================================
    // PEMINJAMAN
    // ==========================================

    Route::get('/peminjaman', [InventarisPeminjamanController::class, 'index'])
        ->name('inventaris.peminjaman');

    Route::get('/peminjaman/filter/{status}', [InventarisPeminjamanController::class, 'filter'])
        ->name('inventaris.peminjaman.filter');

    Route::get('/peminjaman/{id}', [InventarisPeminjamanController::class, 'show'])
        ->name('inventaris.peminjaman.show');

    Route::get('/riwayat', [InventarisPeminjamanController::class, 'riwayat'])
        ->name('inventaris.riwayat');


    // ==========================================
    // PEMINJAMAN BARANG
    // ==========================================

    Route::post('/pinjam', [InventarisController::class, 'pinjam'])
        ->name('inventaris.pinjam.store');

    Route::get('/{id}/pinjam', [InventarisController::class, 'pinjamForm'])
        ->name('inventaris.pinjam');

    Route::post('/kembali/{id}', [InventarisController::class, 'kembali'])
        ->name('inventaris.kembali');
    
    Route::post('/hilang/{id}', [InventarisController::class, 'hilang'])
	->name('inventaris.hilang');


    // ==========================================
    // KHUSUS KETUA & SEKRETARIS
    // ==========================================

    Route::middleware(['role.check:Ketua,Sekretaris'])->group(function () {

        Route::get('/create', [InventarisController::class, 'create'])
            ->name('inventaris.create');

        Route::post('/', [InventarisController::class, 'store'])
            ->name('inventaris.store');

        Route::get('/edit/{id}', [InventarisController::class, 'edit'])
            ->name('inventaris.edit');

        Route::put('/update/{id}', [InventarisController::class, 'update'])
            ->name('inventaris.update');

        Route::delete('/destroy/{id}', [InventarisController::class, 'destroy'])
            ->name('inventaris.destroy');
    });


    // ==========================================
    // DETAIL BARANG
    // ==========================================

    Route::get('/{id}', [InventarisController::class, 'show'])
        ->name('inventaris.show');
});

});

