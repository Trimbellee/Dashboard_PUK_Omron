<?php

namespace App\Http\Controllers\Public\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PesanMasukController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $query = Contact::with('anggota')->latest();

        if ($status === 'unread') {
            $query->where('is_read', false);
        } elseif ($status === 'read') {
            $query->where('is_read', true);
        }

        $pesanMasuk = $query->paginate(10)->withQueryString();

        $totalSemua       = Contact::count();
        $totalBelumDibaca = Contact::where('is_read', false)->count();
        $totalSudahDibaca = $totalSemua - $totalBelumDibaca;

        return view('landing-page.admin.pesan-masuk.index', compact(
            'pesanMasuk', 'totalBelumDibaca', 'totalSemua', 'totalSudahDibaca', 'status'
        ));
    }

    public function markAsRead(Contact $contact): JsonResponse
    {
        $contact->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Pesan ditandai sebagai sudah dibaca.',
        ]);
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $isSekretaris = strtolower(trim(session('anggota.jabatan_puk', ''))) === 'sekretaris';

        if (! $isSekretaris) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus pesan.');
        }

        $contact->delete();

        return redirect()
            ->route('admin.pesan-masuk.index')
            ->with('success', 'Pesan berhasil dihapus.');
    }
}