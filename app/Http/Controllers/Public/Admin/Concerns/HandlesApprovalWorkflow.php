<?php

namespace App\Http\Controllers\Public\Admin\Concerns;

use App\Helpers\PermissionHelper;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait HandlesApprovalWorkflow
{
    protected function submitForApproval(Model $content, string $jabatan, string $type = 'news'): string
    {
        $createPerm = $type === 'news' ? 'create_news' : 'create_gallery';
        $reviewPerm = $type === 'news' ? 'review_news' : 'review_gallery';
        $approvePerm = $type === 'news' ? 'approve_news' : 'approve_gallery';

        $jabatanNormalized = strtolower(trim($jabatan));

        $canCreate = PermissionHelper::can($createPerm, $jabatanNormalized);
        $canReview = PermissionHelper::can($reviewPerm, $jabatanNormalized);
        $canApprove = PermissionHelper::can($approvePerm, $jabatanNormalized);

        if ($canApprove) {
            $nextStatus = 'published';
            $content->update([
                'status' => $nextStatus,
                'published_at' => now(),
                'catatan_revisi' => null,
                'last_edited_at' => now(),
                'last_edited_by' => session('anggota.id'),
            ]);
            ActivityLog::log($content, 'published', 'Berita diterbitkan langsung oleh approver');
            return $nextStatus;
        }

        if ($canReview) {
            $nextStatus = 'submitted_sekretaris';
            $content->update([
                'status' => $nextStatus,
                'published_at' => null,
                'catatan_revisi' => null,
                'last_edited_at' => now(),
                'last_edited_by' => session('anggota.id'),
            ]);
            ActivityLog::log($content, 'submitted', 'Berita dikirim ke Sekretaris untuk approval');
            return $nextStatus;
        }

        if ($canCreate) {
            $nextStatus = 'submitted_bidang5';
            $content->update([
                'status' => $nextStatus,
                'published_at' => null,
                'catatan_revisi' => null,
                'edited_by' => null,
                'last_edited_at' => now(),
                'last_edited_by' => session('anggota.id'),
            ]);
            ActivityLog::log($content, 'submitted', 'Berita dikirim ke reviewer');
            return $nextStatus;
        }

        abort(403, 'Anda tidak memiliki izin untuk submit konten ini.');
    }

    protected function reviewByBidang5(Model $content, bool $approve, ?string $catatan = null, ?array $editedData = null, string $type = 'news'): void
    {
        abort_unless($content->status === 'submitted_bidang5', 400, 'Konten tidak sedang menunggu review.');

        $reviewPerm = $type === 'news' ? 'review_news' : 'review_gallery';
        abort_unless(PermissionHelper::can($reviewPerm), 403, 'Anda tidak memiliki izin untuk mereview.');

        $userId = session('anggota.id');

        $updateData = [
            'reviewed_by_bidang5' => $userId,
            'catatan_revisi' => null,
            'last_edited_at' => now(),
            'last_edited_by' => $userId,
        ];

        if ($approve) {
            $updateData['status'] = 'submitted_sekretaris';

            if ($editedData && !empty($editedData)) {
                $updateData['edited_by'] = $userId;
                $updateData = array_merge($updateData, $editedData);
                ActivityLog::log($content, 'updated', 'Reviewer mengedit dan menyetujui berita', ['changes' => array_keys($editedData)]);
            } else {
                ActivityLog::log($content, 'reviewed', 'Reviewer menyetujui berita tanpa edit');
            }
        } else {
            $updateData['status'] = 'revisi';
            $updateData['catatan_revisi'] = $catatan;
            $updateData['edited_by'] = null;
            ActivityLog::log($content, 'rejected', 'Reviewer menolak berita', ['reason' => $catatan]);
        }

        $content->update($updateData);
    }

    protected function approveBySekretaris(Model $content, bool $approve, ?string $catatan = null, string $type = 'news'): void
    {
        abort_unless($content->status === 'submitted_sekretaris', 400, 'Konten tidak sedang menunggu approval final.');

        $approvePerm = $type === 'news' ? 'approve_news' : 'approve_gallery';
        abort_unless(PermissionHelper::can($approvePerm), 403, 'Anda tidak memiliki izin untuk memberikan approval.');

        $userId = session('anggota.id');

        $updateData = [
            'approved_by_sekretaris' => $userId,
            'catatan_revisi' => null,
            'last_edited_at' => now(),
            'last_edited_by' => $userId,
        ];

        if ($approve) {
            $updateData['status'] = 'published';
            $updateData['published_at'] = now();
            ActivityLog::log($content, 'approved', 'Sekretaris menyetujui dan menerbitkan berita');
        } else {
            $updateData['status'] = 'revisi';
            $updateData['catatan_revisi'] = $catatan;
            ActivityLog::log($content, 'rejected', 'Sekretaris menolak berita', ['reason' => $catatan]);
        }

        $content->update($updateData);
    }

    protected function updateByReviewer(Model $content, array $data, string $type = 'news'): void
    {
        $reviewPerm = $type === 'news' ? 'review_news' : 'review_gallery';

        $userId = session('anggota.id');
        $canReview = PermissionHelper::can($reviewPerm);

        abort_unless(in_array($content->status, ['draft', 'revisi'], true), 400, 'Konten tidak bisa diedit dalam status ini.');
        abort_unless($canReview, 403, 'Anda tidak memiliki izin untuk mengedit.');

        $data['edited_by'] = $userId;
        $data['last_edited_at'] = now();
        $data['last_edited_by'] = $userId;

        $content->update($data);
        ActivityLog::log($content, 'updated', 'Reviewer mengupdate berita');
    }

    protected function updateByCreator(Model $content, array $data): void
    {
        $userId = session('anggota.id');

        abort_unless($content->created_by === $userId, 403, 'Anda tidak berhak mengedit berita ini.');
        abort_unless(in_array($content->status, ['draft', 'revisi', 'scheduled'], true), 400, 'Konten tidak bisa diedit dalam status ini.');

        $data['last_edited_at'] = now();
        $data['last_edited_by'] = $userId;

        $content->update($data);
        ActivityLog::log($content, 'updated', 'Penulis mengupdate berita');
    }

    protected function takedownNews(Model $content, string $reason, string $type = 'news'): void
    {
        $approvePerm = $type === 'news' ? 'approve_news' : 'approve_gallery';
        abort_unless(PermissionHelper::can($approvePerm), 403, 'Anda tidak memiliki izin untuk melakukan takedown.');
        abort_unless($content->status === 'published', 400, 'Hanya berita yang sudah diterbitkan yang bisa di-takedown.');

        $userId = session('anggota.id');

        $content->update([
            'status' => 'takedown',
            'takedown_reason' => $reason,
            'takedown_at' => now(),
            'takedown_by' => $userId,
            'last_edited_at' => now(),
            'last_edited_by' => $userId,
        ]);
        ActivityLog::log($content, 'takedown', 'Berita di-takedown', ['reason' => $reason]);
    }

    protected function restoreNews(Model $content, string $type = 'news'): void
    {
        $approvePerm = $type === 'news' ? 'approve_news' : 'approve_gallery';
        abort_unless(PermissionHelper::can($approvePerm), 403, 'Anda tidak memiliki izin untuk melakukan restore.');
        abort_unless($content->status === 'takedown', 400, 'Hanya berita yang di-takedown yang bisa direstore.');

        $userId = session('anggota.id');

        $content->update([
            'status' => 'published',
            'takedown_reason' => null,
            'takedown_at' => null,
            'takedown_by' => null,
            'last_edited_at' => now(),
            'last_edited_by' => $userId,
        ]);
        ActivityLog::log($content, 'restored', 'Berita di-restore');
    }
}