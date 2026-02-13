<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Models\Category;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('IT Support Request')
                ->columns(2)
                ->schema([
                    // --- Identitas request ---
                    TextInput::make('code')
                        ->label('Request Code')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Otomatis dibuat saat create.'),

                    Select::make('request_type')
                        ->label('Request Type')
                        ->required()
                        ->options([
                            'incident' => 'Incident (Gangguan)',
                            'service_request' => 'Service Request (Permintaan Layanan)',
                            'access_request' => 'Access Request (Akses)',
                            'maintenance' => 'Maintenance (Perawatan)',
                        ])
                        ->default('incident'),

                    TextInput::make('title')
                        ->label('Title / Summary')
                        ->required()
                        ->maxLength(150)
                        ->columnSpan(2),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(6)
                        ->columnSpan(2),

                    // --- Konteks IT (biar jelas “bukan cuma tiket antrean”) ---
                    TextInput::make('asset_tag')
                        ->label('Asset Tag')
                        ->maxLength(50)
                        ->nullable()
                        ->helperText('Contoh: LAP-ITS-0231 / PRN-2F-010'),

                    TextInput::make('location')
                        ->label('Location')
                        ->maxLength(120)
                        ->nullable()
                        ->helperText('Contoh: Ruang Finance Lt 2, Gedung A'),

                    Select::make('category_id')
                        ->label('Category')
                        ->required()
                        ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),

                    Select::make('priority')
                        ->label('Priority')
                        ->required()
                        ->options([
                            'low' => 'Low',
                            'medium' => 'Medium',
                            'high' => 'High',
                            'urgent' => 'Urgent',
                        ])
                        ->default('medium'),

                    Select::make('status')
                        ->label('Status')
                        ->required()
                        ->options([
                            'open' => 'Open',
                            'in_progress' => 'In Progress',
                            'resolved' => 'Resolved',
                            'closed' => 'Closed',
                        ])
                        ->default('open'),

                    Select::make('assignee_id')
                        ->label('Technician / Assignee')
                        ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->nullable(),

                    DateTimePicker::make('due_at')
                        ->label('SLA Due At')
                        ->nullable()
                        ->helperText('Jika kosong, otomatis diisi dari SLA category.'),

                    // --- Kontak pelapor ---
                    TextInput::make('requester_name')
                        ->label('Requester Name')
                        ->maxLength(120)
                        ->nullable(),

                    TextInput::make('requester_email')
                        ->label('Requester Email')
                        ->email()
                        ->maxLength(120)
                        ->nullable(),

                    TextInput::make('contact_phone')
                        ->label('Contact Phone')
                        ->maxLength(30)
                        ->nullable(),
                ]),
        ]);
    }
}
