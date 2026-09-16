<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'kategori', 'judul', 'slug', 'ringkasan', 'konten','konten_delta', 'gambar',
        'status', 'catatan_revisi',
        'created_by', 'reviewed_by_bidang5', 'approved_by_sekretaris',
        'edited_by',
        'published_at', 'views',
        'takedown_reason', 'takedown_at', 'takedown_by',
        'is_featured', 'is_breaking', 'is_headline',
        'scheduled_at',
        'meta_title', 'meta_description',
        'last_edited_at', 'last_edited_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'takedown_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_breaking' => 'boolean',
        'is_headline' => 'boolean',
        'views' => 'integer',
        'last_edited_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_REVISI = 'revisi';
    const STATUS_SUBMITTED_BIDANG5 = 'submitted_bidang5';
    const STATUS_SUBMITTED_SEKRETARIS = 'submitted_sekretaris';
    const STATUS_PUBLISHED = 'published';
    const STATUS_TAKEDOWN = 'takedown';
    const STATUS_SCHEDULED = 'scheduled';

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeBreaking($query)
    {
        return $query->where('is_breaking', true);
    }

    public function scopeHeadline($query)
    {
        return $query->where('is_headline', true);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
                     ->where('scheduled_at', '<=', now());
    }

    // Relations
    public function creator()
    {
        return $this->belongsTo(Anggota::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(Anggota::class, 'edited_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(Anggota::class, 'reviewed_by_bidang5');
    }

    public function approver()
    {
        return $this->belongsTo(Anggota::class, 'approved_by_sekretaris');
    }

    public function takedownBy()
    {
        return $this->belongsTo(Anggota::class, 'takedown_by');
    }

    public function lastEditedBy()
    {
        return $this->belongsTo(Anggota::class, 'last_edited_by');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'news_tag');
    }

    public function logs()
    {
        return $this->morphMany(ActivityLog::class, 'loggable')->latest();
    }

    // Methods
    public function incrementViews()
    {
        $this->increment('views');
    }

    public function isTakedown(): bool
    {
        return !is_null($this->takedown_at);
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED
            && $this->scheduled_at;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function getReadingTime(): int
    {
        $wordCount = str_word_count(strip_tags($this->konten));
        return ceil($wordCount / 200); // 200 kata per menit
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'submitted_sekretaris' => 'Menunggu Approval',
            'submitted_bidang5' => 'Menunggu Review',
            'published' => 'Sudah Terbit',
            'takedown' => 'Takedown',
            'scheduled' => 'Terjadwal',
            default => str_replace('_', ' ', $this->status),
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'revisi' => 'orange',
            'submitted_bidang5' => 'yellow',
            'submitted_sekretaris' => 'amber',
            'published' => 'green',
            'takedown' => 'red',
            'scheduled' => 'blue',
            default => 'gray',
        };
    }
}