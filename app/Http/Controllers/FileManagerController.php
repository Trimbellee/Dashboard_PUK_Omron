<?php

namespace App\Http\Controllers;

use App\Helpers\PdfCompressor;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FileManagerController extends Controller
{
    /** Disk PRIVAT tempat file disimpan (storage/app/private). Tidak bisa diakses lewat URL langsung. */
    private const DISK = 'local';

    /* ============================================================
     | VIEW
     ============================================================ */
    public function index(Request $req)
    {
        $parentId    = $req->filled('folder') ? (int) $req->get('folder') : null;
        $breadcrumbs = $this->breadcrumbsFor($parentId);

        return view('filemanager.index', compact('parentId', 'breadcrumbs'));
    }

    /* ============================================================
     | DATA (list / search / filter / sort / pagination)
     ============================================================ */
    public function data(Request $request)
    {
        $search = trim((string) $request->search);
        $filter = (string) $request->filter;
        $folder = $request->filled('folder') ? (int) $request->folder : null;

        $query = File::query();

        if ($search === '') {
            $query->where('parent_id', $folder);
        } else {
            // pencarian global di semua folder
            $query->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%');
        }

        // filter jenis file
        $extMap = [
            'pdf'   => ['pdf'],
            'word'  => ['doc', 'docx'],
            'excel' => ['xls', 'xlsx'],
        ];

        if (isset($extMap[$filter])) {
            $query->where(function ($q) use ($extMap, $filter, $search) {
                if ($search === '') {
                    $q->where('type', 'folder'); // folder tetap tampil agar bisa dinavigasi
                }
                $q->orWhere(function ($q2) use ($extMap, $filter) {
                    $q2->where('type', 'file')->where(function ($q3) use ($extMap, $filter) {
                        foreach ($extMap[$filter] as $e) {
                            $q3->orWhere('name', 'like', '%.' . $e);
                        }
                    });
                });
            });
        }

        // sort: folder selalu di atas
        $dir = $request->sort_direction === 'desc' ? 'desc' : 'asc';
        $query->orderByRaw("CASE WHEN type = 'folder' THEN 0 ELSE 1 END");

        switch ($request->sort_field) {
            case 'date':
                $query->orderBy('updated_at', $dir);
                break;
            case 'size':
                $query->orderBy('size', $dir);
                break;
            default:
                $query->orderBy('name', $dir);
        }
        $query->orderBy('id');

        $files = $query->paginate(100);

        $folderMap = $search !== ''
            ? File::where('type', 'folder')->get(['id', 'name', 'parent_id'])->keyBy('id')
            : null;

        $items = collect($files->items())->map(function ($f) use ($folderMap) {
            return [
                'id'                   => $f->id,
                'name'                 => $f->name,
                'type'                 => $f->type,
                'ext'                  => $f->type === 'file' ? strtolower(pathinfo($f->name, PATHINFO_EXTENSION)) : null,
                'parent_id'            => $f->parent_id,
                'size'                 => $f->size,
                'url'                  => $f->path ? '/file-manager/file/' . $f->id : null,
                'updated_at'           => optional($f->updated_at)->toIso8601String(),
                'updated_at_formatted' => optional($f->updated_at)->format('d M Y H:i'),
                'location'             => $folderMap ? $this->locationOf($f->parent_id, $folderMap) : null,
            ];
        })->values();

        $response = [
            'data'         => $items,
            'has_more'     => $files->hasMorePages(),
            'current_page' => $files->currentPage(),
        ];

        if ($files->currentPage() === 1) {
            $response['breadcrumbs'] = $this->breadcrumbsFor($folder);
            $response['stats'] = [
                'files' => File::where('type', 'file')->count(),
                'size'  => (int) File::where('type', 'file')->sum('size'),
            ];
        }

        return response()->json($response);
    }

    /* ============================================================
     | FOLDER
     ============================================================ */
    public function storeFolder(Request $req)
    {
        $v = Validator::make($req->all(), [
            'name'      => 'required|string|max:255',
            'parent_id' => 'nullable|integer',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $name = trim($req->name);

        if ($this->invalidName($name)) {
            return response()->json(['success' => false, 'message' => 'Nama mengandung karakter yang tidak diperbolehkan']);
        }

        $exists = File::where('parent_id', $req->parent_id)
            ->where('name', $name)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Folder dengan nama sama sudah ada',
            ]);
        }

        File::create([
            'name'      => $name,
            'type'      => 'folder',
            'parent_id' => $req->parent_id,
        ]);

        return response()->json(['success' => true]);
    }

    /* ============================================================
     | UPLOAD  (PDF dikompres, ukuran disimpan)
     ============================================================ */
    public function upload(Request $req)
    {
        $v = Validator::make($req->all(), [
            'file'      => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'parent_id' => 'nullable|integer',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $file = $req->file('file');
        $name = $file->getClientOriginalName();
        $ext  = strtolower($file->getClientOriginalExtension());

        $exists = File::where('parent_id', $req->parent_id)
            ->where('name', $name)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'File dengan nama sama sudah ada',
            ]);
        }

        $disk       = $this->privateDisk();
        $storedName = now()->format('Ymd_His') . '_' . Str::random(8) . '.' . $ext;
        $path       = 'filemanager/' . $storedName;

        if ($ext === 'pdf') {
            $disk->put($path, PdfCompressor::compress($file->getRealPath()));
        } else {
            $file->storeAs('filemanager', $storedName, self::DISK);
        }

        $model = new File();
        $model->forceFill([
            'name'      => $name,
            'type'      => 'file',
            'path'      => $path,
            'size'      => $disk->size($path),
            'parent_id' => $req->parent_id,
        ])->save();

        return response()->json(['success' => true]);
    }

    /* ============================================================
     | DOWNLOAD
     ============================================================ */
    public function download($id)
    {
        $file = File::where('type', 'file')->findOrFail($id);
        $disk = $this->diskFor($file->path);

        abort_unless($disk, 404);

        return $disk->download($file->path, $file->name);
    }

    /* ============================================================
     | VIEW INLINE (hanya untuk user yang sudah login)
     ============================================================ */
    public function view($id)
    {
        $file = File::where('type', 'file')->findOrFail($id);
        $disk = $this->diskFor($file->path);

        abort_unless($disk, 404);

        // hanya PDF yang ditampilkan inline; jenis lain langsung diunduh
        if (strtolower(pathinfo($file->name, PATHINFO_EXTENSION)) !== 'pdf') {
            return $disk->download($file->path, $file->name);
        }

        return $disk->response($file->path, $file->name, [
            'Content-Type'           => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, max-age=0, must-revalidate',
        ], 'inline');
    }

    /* ============================================================
     | RENAME  (ekstensi file tidak boleh berubah)
     ============================================================ */
    public function rename(Request $req, $id)
    {
        $file = File::findOrFail($id);

        $v = Validator::make($req->all(), ['name' => 'required|string|max:255']);

        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $name = trim($req->name);

        if ($this->invalidName($name)) {
            return response()->json(['success' => false, 'message' => 'Nama mengandung karakter yang tidak diperbolehkan']);
        }

        if ($file->type === 'file') {
            $ext = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));

            if ($ext !== '' && !Str::endsWith(strtolower($name), '.' . $ext)) {
                $name .= '.' . $ext;
            }
        }

        $exists = File::where('parent_id', $file->parent_id)
            ->where('name', $name)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Nama sudah digunakan',
            ]);
        }

        $file->update(['name' => $name]);

        return response()->json(['success' => true]);
    }

    /* ============================================================
     | DELETE / BULK DELETE (ke Trash)
     ============================================================ */
    public function delete($id)
    {
        $file = File::findOrFail($id);

        $this->recursiveDelete($file);

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $req)
    {
        $ids = $req->ids ?? [];

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada item dipilih',
            ]);
        }

        $files = File::whereIn('id', $ids)->get();

        foreach ($files as $file) {
            $this->recursiveDelete($file);
        }

        return response()->json(['success' => true]);
    }

    /* ============================================================
     | MOVE
     ============================================================ */
    public function move(Request $req)
    {
        $ids    = $req->ids ?? [];
        $target = $req->target_id ?: null;

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada item',
            ]);
        }

        foreach ($ids as $id) {
            $item = File::findOrFail($id);

            if ($target == $item->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Folder tidak bisa dipindah ke dirinya sendiri',
                ]);
            }

            if ($item->type === 'folder' && $this->isChildOf($target, $item->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Folder tidak bisa dipindah ke dalam child-nya sendiri',
                ]);
            }

            $exists = File::where('parent_id', $target)
                ->where('name', $item->name)
                ->where('id', '!=', $item->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => $item->name . ' sudah ada yang sama di folder tujuan',
                ]);
            }

            $item->update(['parent_id' => $target]);
        }

        return response()->json(['success' => true]);
    }

    /* ============================================================
     | FOLDER TREE (untuk dialog "Pindahkan ke...")
     ============================================================ */
    public function foldersTree()
    {
        $folders = File::where('type', 'folder')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return response()->json($this->buildFolderTree($folders));
    }

    /* ============================================================
     | TRASH
     ============================================================ */
    public function trash()
    {
        $all = File::onlyTrashed()->get();

        $trashedIds = $all->pluck('id')->flip();

        // tampilkan hanya item "teratas": yang parent-nya tidak ikut terhapus
        $files = $all->filter(fn ($f) => !$f->parent_id || !isset($trashedIds[$f->parent_id]))->values();

        $items = $files->map(function ($f) {
            return [
                'id'        => $f->id,
                'name'      => $f->name,
                'type'      => $f->type,
                'ext'       => $f->type === 'file' ? strtolower(pathinfo($f->name, PATHINFO_EXTENSION)) : null,
                'size'      => $f->size,
                'deleted'   => $f->deleted_at ? $f->deleted_at->locale('id')->diffForHumans() : '',
                'remaining' => $f->deleted_at ? max(0, (int) ceil(30 - $f->deleted_at->diffInDays(now()))) : 0,
            ];
        })->values();

        return view('filemanager.trash', compact('items'));
    }

    public function restore($id)
    {
        $file = File::onlyTrashed()->findOrFail($id);

        $this->recursiveRestore($file);

        return response()->json(['success' => true]);
    }

    public function forceDelete($id)
    {
        $file = File::onlyTrashed()->findOrFail($id);

        $this->recursiveForceDelete($file);

        return response()->json(['success' => true]);
    }

    public function emptyTrash()
    {
        $trashed    = File::onlyTrashed()->get();
        $trashedIds = $trashed->pluck('id')->flip();

        foreach ($trashed->filter(fn ($f) => !$f->parent_id || !isset($trashedIds[$f->parent_id])) as $file) {
            $this->recursiveForceDelete($file);
        }

        return response()->json(['success' => true]);
    }

    public function autoCleanTrash()
    {
        $old        = File::onlyTrashed()->where('deleted_at', '<=', now()->subDays(30))->get();
        $trashedIds = File::onlyTrashed()->pluck('id')->flip();

        foreach ($old as $file) {
            // item yang folder induknya juga ada di Trash ikut dibersihkan bersama induknya
            if ($file->parent_id && isset($trashedIds[$file->parent_id])) {
                continue;
            }

            $this->recursiveForceDelete($file);
        }
    }

    /* ============================================================
     | HELPERS
     ============================================================ */
    private function privateDisk()
    {
        return Storage::disk(self::DISK);
    }

    /**
     * Disk tempat sebuah file berada: privat dulu, lalu fallback ke disk public
     * untuk file lama yang belum dimigrasi (php artisan filemanager:make-private).
     */
    private function diskFor(?string $path)
    {
        if (!$path) {
            return null;
        }

        $private = $this->privateDisk();

        if ($private->exists($path)) {
            return $private;
        }

        $public = Storage::disk('public');

        return $public->exists($path) ? $public : null;
    }

    private function invalidName(string $name): bool
    {
        return $name === '' || preg_match('/[\/\\\\:*?"<>|]/', $name) === 1;
    }

    private function breadcrumbsFor($id): array
    {
        $crumbs = [];
        $guard  = 0;
        $cur    = $id ? File::find($id) : null;

        while ($cur && $guard++ < 50) {
            array_unshift($crumbs, ['id' => $cur->id, 'name' => $cur->name]);
            $cur = $cur->parent_id ? File::find($cur->parent_id) : null;
        }

        return $crumbs;
    }

    private function locationOf($parentId, $folderMap): string
    {
        $parts = [];
        $guard = 0;

        while ($parentId && isset($folderMap[$parentId]) && $guard++ < 50) {
            array_unshift($parts, $folderMap[$parentId]->name);
            $parentId = $folderMap[$parentId]->parent_id;
        }

        return 'Home' . ($parts ? ' / ' . implode(' / ', $parts) : '');
    }

    private function recursiveDelete($file): void
    {
        if ($file->type === 'folder') {
            foreach (File::where('parent_id', $file->id)->get() as $child) {
                $this->recursiveDelete($child);
            }
        }

        $file->delete();
    }

    private function recursiveRestore($file): void
    {
        $file->restore();

        if ($file->type === 'folder') {
            foreach (File::onlyTrashed()->where('parent_id', $file->id)->get() as $child) {
                $this->recursiveRestore($child);
            }
        }
    }

    private function recursiveForceDelete($file): void
    {
        if ($file->type === 'folder') {
            foreach (File::withTrashed()->where('parent_id', $file->id)->get() as $child) {
                $this->recursiveForceDelete($child);
            }
        }

        if ($file->type === 'file') {
            // hapus dari disk privat dan (untuk file lama yang belum dimigrasi) disk public
            foreach ([$this->privateDisk(), Storage::disk('public')] as $disk) {
                if ($file->path) {
                    $disk->delete($file->path);
                }
            }
        }

        $file->forceDelete();
    }

    private function isChildOf($targetId, $parentId): bool
    {
        $guard = 0;

        while ($targetId && $guard++ < 100) {
            $target = File::find($targetId);

            if (!$target) {
                return false;
            }

            if ($target->parent_id == $parentId) {
                return true;
            }

            $targetId = $target->parent_id;
        }

        return false;
    }

    private function buildFolderTree($folders, $parentId = null): array
    {
        $branch = [];

        foreach ($folders as $folder) {
            if ($folder->parent_id == $parentId) {
                $branch[] = [
                    'id'       => $folder->id,
                    'name'     => $folder->name,
                    'children' => $this->buildFolderTree($folders, $folder->id),
                ];
            }
        }

        return $branch;
    }
}