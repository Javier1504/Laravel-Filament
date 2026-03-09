<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HelpdeskSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('filament.auth.guard', 'web');

        Permission::findOrCreate('manage categories', $guard);
        Permission::findOrCreate('manage all requests', $guard);

        $adminRole = Role::findOrCreate('admin', $guard);
        $techRole = Role::findOrCreate('technician', $guard);
        $userRole = Role::findOrCreate('user', $guard);

        $adminRole->syncPermissions(['manage categories', 'manage all requests']);
        $techRole->syncPermissions([]);
        $userRole->syncPermissions([]);

        $admin = User::updateOrCreate(
            ['email' => 'admin@local.test'],
            ['name' => 'Admin', 'password' => Hash::make('Admin@123')]
        );
        $admin->syncRoles(['admin']);

        $tech = User::updateOrCreate(
            ['email' => 'tech@local.test'],
            ['name' => 'Technician', 'password' => Hash::make('Tech@1234')]
        );
        $tech->syncRoles(['technician']);

        $requester = User::updateOrCreate(
            ['email' => 'user@local.test'],
            ['name' => 'Requester', 'password' => Hash::make('User@1234')]
        );
        $requester->syncRoles(['user']);

        $general = Category::updateOrCreate(
            ['slug' => 'general'],
            ['name' => 'General', 'description' => 'General IT requests', 'is_active' => true]
        );

        $ticket = Ticket::updateOrCreate(
            ['code' => 'TCK-000001'],
            [
                'title' => 'Laptop tidak bisa connect WiFi',
                'description' => 'Laptop user tidak bisa terhubung ke WiFi kantor sejak pagi.',
                'request_type' => 'incident',
                'asset_tag' => 'LTP-REQ-01',
                'location' => 'Ruang Operasional',
                'category_id' => $general->id,
                'priority' => 'high',
                'status' => Ticket::STATUS_PENDING_REVIEW,
                'requester_id' => $requester->id,
                'assignee_id' => $tech->id,
                'due_at' => now()->addHours(6),
            ]
        );

        if ($ticket->comments()->count() === 0) {
            $ticket->comments()->createMany([
                [
                    'user_id' => $tech->id,
                    'body' => 'Ringkasan: Sudah dilakukan pengecekan adapter WiFi dan reset network stack.\n\nDetail: Setelah reset adapter dan renew IP, koneksi kembali normal. Mohon review admin.',
                    'is_internal' => true,
                    'comment_type' => Ticket::COMMENT_PROGRESS,
                ],
                [
                    'user_id' => $admin->id,
                    'body' => 'Mohon cek kembali kestabilan koneksi selama 1 jam sebelum approve final.',
                    'is_internal' => true,
                    'comment_type' => Ticket::COMMENT_REVIEW,
                ],
            ]);
        }

        if ($ticket->events()->count() === 0) {
            $ticket->recordEvent($admin, 'created', null, Ticket::STATUS_NEW);
            $ticket->recordEvent($admin, 'assigned', Ticket::STATUS_NEW, Ticket::STATUS_ASSIGNED, ['to_assignee_id' => $tech->id]);
            $ticket->recordEvent($tech, 'progress_submitted', Ticket::STATUS_ON_GOING, Ticket::STATUS_PENDING_REVIEW);
        }
    }
}
