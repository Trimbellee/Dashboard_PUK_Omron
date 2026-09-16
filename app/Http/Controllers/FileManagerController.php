<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class FileManagerController extends Controller
{
    public function index(Request $req)
    {
        $parentId = $req->get('folder');

        $breadcrumbs = [];

        if($parentId){
            $current = \App\Models\File::find($parentId);

            while($current){
                array_unshift($breadcrumbs, $current);
                $current = $current->parent;
            }
        }

        return view('filemanager.index', compact('parentId','breadcrumbs'));
    }

        public function data(Request $request)
    {
        $query = File::query()
            ->where('parent_id', $request->filled('folder') ? $request->folder : null);

        // SEARCH BACKEND
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // SORT
        $sortField = $request->sort_field ?? 'name';
        $sortDirection = $request->sort_direction ?? 'asc';

        // folder selalu di atas
        $query->orderByRaw("
            CASE
                WHEN type = 'folder' THEN 0
                ELSE 1
            END
        ");

        if ($sortField === 'date') {
            $query->orderBy('updated_at', $sortDirection);
        } else {
            $query->orderBy('name', $sortDirection);
        }

        $files = $query->paginate(100);

        return response()->json([
            'data' => collect($files->items())->map(function ($item) {

                $item->updated_at_formatted =
                    optional($item->updated_at)?->format('d M Y H:i');

                // URL PUBLIC STORAGE
                $item->url = $item->path
                    ? Storage::url($item->path)
                    : null;

                return $item;

            }),

            'has_more' => $files->hasMorePages(),
            'current_page' => $files->currentPage(),
        ]);
    }

    public function storeFolder(Request $req)
    {
        $exists = File::where('parent_id', $req->parent_id)
            ->where('name', $req->name)
            ->exists();

        if($exists){
            return response()->json([
                'success' => false,
                'message' => 'Folder dengan nama sama sudah ada'
            ]);
        }

        File::create([
            'name' => $req->name,
            'type' => 'folder',
            'parent_id' => $req->parent_id
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    public function upload(Request $req)
    {
        $file = $req->file('file');

        $exists = File::where('parent_id', $req->parent_id)
            ->where('name', $file->getClientOriginalName())
            ->exists();

        if($exists){
            return response()->json([
                'success' => false,
                'message' => 'File dengan nama sama sudah ada'
            ]);
        }

        $path = $file->storeAs(
            'filemanager',
            time().'_'.$file->getClientOriginalName(),
            'public'
        );

        File::create([
            'name' => $file->getClientOriginalName(),
            'type' => 'file',
            'path' => $path,
            'parent_id' => $req->parent_id
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    public function delete($id)
    {
        $file = File::findOrFail($id);

        $this->recursiveDelete($file);

        return response()->json([
            'success' => true
        ]);
    }

    public function rename(Request $req, $id)
    {
        $file = File::findOrFail($id);

        $exists = File::where('parent_id', $file->parent_id)
            ->where('name', $req->name)
            ->where('id', '!=', $id)
            ->exists();

        if($exists){
            return response()->json([
                'success' => false,
                'message' => 'Nama sudah digunakan'
            ]);
        }

        $file->update([
            'name' => $req->name
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    public function trash()
    {
        $all = File::onlyTrashed()->get();

        $files = $all->filter(function($file){

            // kalau parent tidak ada → tampilkan
            if(!$file->parent_id){
                return true;
            }

            // cek parent ikut trash atau tidak
            $parentTrashed = File::onlyTrashed()
                ->where('id', $file->parent_id)
                ->exists();

            // tampilkan kalau parent tidak ikut trash
            return !$parentTrashed;
        });

        return view('filemanager.trash', compact('files'));
    }

    public function restore($id)
    {
        $file = File::onlyTrashed()->findOrFail($id);

        $this->recursiveRestore($file);

        return response()->json([
            'success' => true
        ]);
    }

    public function move(Request $req)
    {
        $ids = $req->ids ?? [];
        $target = $req->target_id ?: null;

        if(empty($ids)){
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada item'
            ]);
        }

        foreach($ids as $id){

            $item = File::findOrFail($id);

            // tidak boleh pindah ke dirinya sendiri
            if($target == $item->id){
                return response()->json([
                    'success' => false,
                    'message' => 'Folder tidak bisa dipindah ke dirinya sendiri'
                ]);
            }

            // folder tidak boleh dipindah ke child-nya sendiri
            if($item->type === 'folder'){

                if($this->isChildOf($target, $item->id)){
                    return response()->json([
                        'success' => false,
                        'message' => 'Folder tidak bisa dipindah ke dalam child-nya sendiri'
                    ]);
                }
            }

            // cek duplicate nama
            $exists = File::where('parent_id', $target)
                ->where('name', $item->name)
                ->where('id', '!=', $item->id)
                ->exists();

            if($exists){
                return response()->json([
                    'success' => false,
                    'message' => $item->name . ' sudah ada yang sama di folder tujuan'
                ]);
            }

            $item->update([
                'parent_id' => $target
            ]);
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function forceDelete($id)
    {
        $file = File::onlyTrashed()->findOrFail($id);

        $this->recursiveForceDelete($file);

        return response()->json([
            'success' => true
        ]);
    }

    public function emptyTrash()
    {
        $files = File::onlyTrashed()
            ->whereNull('parent_id')
            ->get();

        foreach($files as $file){
            $this->recursiveForceDelete($file);
        }

        return response()->json([
            'success' => true
        ]);
    }

    private function recursiveDelete($file)
    {
        // kalau folder → delete semua child
        if($file->type === 'folder'){

            $children = File::where('parent_id', $file->id)->get();

            foreach($children as $child){
                $this->recursiveDelete($child);
            }
        }

        $file->delete();
    }

    public function bulkDelete(Request $req)
    {
        $ids = $req->ids ?? [];

        if(empty($ids)){
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada item dipilih'
            ]);
        }

        $files = File::whereIn('id', $ids)->get();

        foreach($files as $file){
            $this->recursiveDelete($file);
        }

        return response()->json([
            'success' => true
        ]);
    }

    private function recursiveRestore($file)
    {
        // restore parent dulu
        $file->restore();

        // restore child
        if($file->type === 'folder'){

            $children = File::onlyTrashed()
                ->where('parent_id', $file->id)
                ->get();

            foreach($children as $child){
                $this->recursiveRestore($child);
            }
        }
    }

    private function recursiveForceDelete($file)
    {
        // hapus child dulu
        if($file->type === 'folder'){

            $children = File::withTrashed()->where('parent_id', $file->id)->get();

            foreach($children as $child){
                $this->recursiveForceDelete($child);
            }
        }

        // hapus file fisik
        if($file->type === 'file' && $file->path){
            Storage::disk('public')->delete($file->path);
        }

        $file->forceDelete();
    }

    private function isChildOf($targetId, $parentId)
    {
        while($targetId){

            $target = File::find($targetId);

            if(!$target){
                return false;
            }

            if($target->parent_id == $parentId){
                return true;
            }

            $targetId = $target->parent_id;
        }

        return false;
    }

    public function foldersTree()
    {
        $folders = File::where('type', 'folder')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'parent_id'
            ]);

        $tree = $this->buildFolderTree($folders);

        return response()->json($tree);
    }

    private function buildFolderTree($folders, $parentId = null)
    {
        $branch = [];

        foreach ($folders as $folder) {

            if ($folder->parent_id == $parentId) {

                $children = $this->buildFolderTree($folders, $folder->id);

                $branch[] = [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'children' => $children
                ];
            }
        }

        return $branch;
    }

    public function autoCleanTrash()
    {
        $files = File::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays(30))
            ->whereNull('parent_id')
            ->get();

        foreach($files as $file){
            $this->recursiveForceDelete($file);
        }
    }
}

