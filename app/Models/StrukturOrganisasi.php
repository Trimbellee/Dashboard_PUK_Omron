<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class StrukturOrganisasi extends Model
{
    use SoftDeletes;

    protected $table = 'struktur_organisasi';

    protected $fillable = [
        'parent_id',
        'jabatan',
        'anggota_id',
        'urutan',
    ];

    public function children()
    {
        return $this->hasMany(StrukturOrganisasi::class, 'parent_id')->orderBy('urutan');
    }

    public function parent()
    {
        return $this->belongsTo(StrukturOrganisasi::class, 'parent_id');
    }

    public function anggota()
    {
        return $this->belongsTo(Anggota::class, 'anggota_id');
    }

    protected static function booted()
    {
        static::saved(function ($struktur) {
            if ($struktur->anggota_id) {
                $anggota = Anggota::where('id', $struktur->anggota_id)->where('is_active', true)->first();
                if ($anggota) {
                    $anggota->syncJabatanPuk();
                }
            }
        });

        static::deleted(function ($struktur) {
            if ($struktur->anggota_id) {
                $anggota = Anggota::where('id', $struktur->anggota_id)->where('is_active', true)->first();
                if ($anggota) {
                    $anggota->syncJabatanPuk();
                }
            }
        });

        static::restored(function ($struktur) {
            if ($struktur->anggota_id) {
                $anggota = Anggota::where('id', $struktur->anggota_id)->where('is_active', true)->first();
                if ($anggota) {
                    $anggota->syncJabatanPuk();
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Ambil semua struktur dalam bentuk tree (rekursif)
     */
    public static function getTree()
    {
        $all = self::with('anggota')->orderBy('urutan')->get();
        $grouped = $all->groupBy('parent_id');

        $build = function ($parentId = null) use (&$build, $grouped) {
            $result = [];
            foreach ($grouped->get($parentId, collect()) as $node) {
                $item = $node->toArray();
                $item['children'] = $build($node->id);
                $item['anggota'] = $node->anggota;
                $result[] = $item;
            }
            return $result;
        };

        return collect($build(null));
    }

    public function toTree()
    {
        return [
            'id'       => $this->id,
            'jabatan'  => $this->jabatan,
            'anggota'  => $this->anggota,
            'urutan'   => $this->urutan,
            'level'    => 0,
            'children' => $this->children->map(function ($child) {
                return $child->toTree();
            }),
        ];
    }

    /**
     * Hapus semua anak (rekursif) — digunakan untuk cascade delete
     */
    public function deleteWithChildren()
    {
        foreach ($this->children as $child) {
            $child->deleteWithChildren();
        }
        $this->delete();
    }
    
    protected function jabatan(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => mb_strtoupper(trim($value), 'UTF-8'),
        );
    }
}
