<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class TicketForm
{
    private static function slaHours(string $priority): int
    {
        return match ($priority) {
            'urgent' => 2,
            'high' => 6,
            'medium' => 24,
            default => 72,
        };
    }

    private static function isAdmin(): bool
    {
        return (bool) Auth::user()?->hasRole('admin');
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ticket')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label('Code')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Otomatis dibuat saat create.'),

                    Select::make('request_type')
                        ->label('Type')
                        ->options(Ticket::requestTypeOptions())
                        ->required()
                        ->default('incident'),

                    TextInput::make('title')
                        ->label('Summary')
                        ->required()
                        ->maxLength(150)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(5)
                        ->required()
                        ->columnSpanFull(),

                    Select::make('category_id')
                        ->label('Category')
                        ->options(fn () => Category::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Select::make('priority')
                        ->label('Priority')
                        ->options(Ticket::priorityOptions())
                        ->required()
                        ->default('medium')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if ($get('due_at')) {
                                return;
                            }

                            $set('due_at', now()->addHours(self::slaHours((string) $state)));
                        }),

                    TextInput::make('asset_tag')
                        ->label('Asset tag')
                        ->maxLength(80)
                        ->nullable(),

                    TextInput::make('location')
                        ->label('Location')
                        ->maxLength(120)
                        ->nullable(),

                    Select::make('assignee_id')
                        ->label('Technician')
                        ->options(fn () => User::role('technician')->orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->visible(fn () => self::isAdmin())
                        ->helperText('Hanya admin yang menentukan technician.'),

                    Select::make('status')
                        ->label('Status')
                        ->options(Ticket::statusOptions())
                        ->visible(fn () => self::isAdmin())
                        ->default(Ticket::STATUS_NEW),

                    DateTimePicker::make('due_at')
                        ->label('Due At (Deadline)')
                        ->timezone('Asia/Jakarta')
                        ->seconds(false)
                        ->required()
                        ->default(fn (callable $get) => now()->addHours(self::slaHours((string) ($get('priority') ?? 'medium')))),

                    Select::make('requester_id')
                        ->label('Requester')
                        ->options(fn () => User::role('user')->orderBy('name')->pluck('name', 'id')->toArray())
                        ->default(fn () => Auth::id())
                        ->searchable()
                        ->preload()
                        ->visible(fn () => self::isAdmin())
                        ->required(),

                    Hidden::make('requester_id')
                        ->default(fn () => Auth::id())
                        ->visible(fn () => ! self::isAdmin()),
                ]),
        ]);
    }
}
