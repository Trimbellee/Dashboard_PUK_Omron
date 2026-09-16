<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\FileManagerController;

class CleanTrash extends Command
{
    protected $signature = 'trash:clean';

    protected $description =
        'Hapus permanen file trash lebih dari 30 hari';

    public function handle()
    {
        app(FileManagerController::class)
            ->autoCleanTrash();

        $this->info('Trash lama berhasil dibersihkan');
    }
}