<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ImportUsers extends Command
{
    protected $signature = 'users:import {file=storage/app/users.csv}';
    protected $description = 'Import users dari CSV ke MySQL';

    public function handle()
    {
        $file = base_path($this->argument('file'));

        if (!file_exists($file)) {
            $this->error("❌ File CSV tidak ditemukan: $file");
            return 1;
        }

        $rows = array_map('str_getcsv', file($file));
        $header = array_shift($rows);

        $inserted = 0;
        $updated  = 0;

        foreach ($rows as $row) {
            $data = array_combine($header, $row);
            if (!$data) continue;

            $user = User::where('username', $data['Username'])->first();

            if ($user) {
                // Update user existing
                $user->email    = $data['Email'];
                $user->role     = $data['Role'];
                $user->password = Hash::make($data['Password']);
                $user->save();
                $updated++;
                $this->line("Updated: {$data['Username']}");
            } else {
                // Insert user baru
                User::create([
                    'username' => $data['Username'],
                    'email'    => $data['Email'],
                    'password' => Hash::make($data['Password']),
                    'role'     => $data['Role']
                ]);
                $inserted++;
                $this->line("Inserted: {$data['Username']}");
            }
        }

        $this->info("✅ Selesai migrasi CSV ke MySQL");
        $this->info("Inserted: $inserted | Updated: $updated");
        return 0;
    }
}
