<?php

namespace App\Console\Commands;

use App\Models\RolePermission;
use App\Helpers\RoleHelper;
use Illuminate\Console\Command;

class NormalizeRolePermissions extends Command
{
    protected $signature = 'role-permission:normalize';
    protected $description = 'Normalize all role names in role_permissions table to Title Case';

    public function handle()
    {
        $this->info('🔄 Normalizing role names in role_permissions table...');
        
        $permissions = RolePermission::all();
        $bar = $this->output->createProgressBar($permissions->count());
        
        foreach ($permissions as $perm) {
            $normalized = RoleHelper::normalize($perm->role);
            if ($normalized !== $perm->role) {
                $perm->role = $normalized;
                $perm->save();
                $this->line("  Changed: '{$perm->role}' -> '{$normalized}'");
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info('✅ Done! All roles normalized.');
    }
}