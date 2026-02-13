<?php

namespace App\Filament\Resources\Tickets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Overview')
                ->columns(2)
                ->schema([
                    TextEntry::make('code')
                        ->label('Request Code')
                        ->copyable(),

                    TextEntry::make('request_type')
                        ->label('Request Type')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'incident' => 'Incident (Gangguan)',
                            'service_request' => 'Service Request',
                            'access_request' => 'Access Request',
                            'maintenance' => 'Maintenance',
                            default => (string) $state,
                        })
                        ->color(fn ($state) => match ($state) {
                            'incident' => 'danger',
                            'service_request' => 'info',
                            'access_request' => 'warning',
                            'maintenance' => 'success',
                            default => 'gray',
                        }),

                    TextEntry::make('category.name')
                        ->label('Category')
                        ->placeholder('-'),

                    TextEntry::make('assignee.name')
                        ->label('Technician / Assignee')
                        ->placeholder('-'),

                    TextEntry::make('priority')
                        ->label('Priority')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'low' => 'Low',
                            'medium' => 'Medium',
                            'high' => 'High',
                            'urgent' => 'Urgent',
                            default => (string) $state,
                        })
                        ->color(fn ($state) => match ($state) {
                            'low' => 'gray',
                            'medium' => 'warning',
                            'high' => 'danger',
                            'urgent' => 'danger',
                            default => 'gray',
                        }),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'open' => 'Open',
                            'in_progress' => 'In Progress',
                            'resolved' => 'Resolved',
                            'closed' => 'Closed',
                            default => (string) $state,
                        })
                        ->color(fn ($state) => match ($state) {
                            'open' => 'warning',
                            'in_progress' => 'info',
                            'resolved' => 'success',
                            'closed' => 'gray',
                            default => 'gray',
                        }),

                    TextEntry::make('is_overdue')
                        ->label('Overdue')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                        ->color(fn ($state) => $state ? 'danger' : 'success'),

                    TextEntry::make('due_at')
                        ->label('SLA Due At')
                        ->dateTime('d M Y H:i')
                        ->placeholder('-'),

                    TextEntry::make('title')
                        ->label('Summary')
                        ->columnSpan(2),

                    TextEntry::make('description')
                        ->label('Description')
                        ->columnSpan(2)
                        ->markdown()
                        ->placeholder('-'),
                ]),

            \Filament\Schemas\Components\Section::make('IT Context')
                ->columns(2)
                ->schema([
                    TextEntry::make('asset_tag')
                        ->label('Asset Tag')
                        ->placeholder('-'),

                    TextEntry::make('location')
                        ->label('Location')
                        ->placeholder('-'),

                    TextEntry::make('requester_name')
                        ->label('Requester Name')
                        ->placeholder('-'),

                    TextEntry::make('requester_email')
                        ->label('Requester Email')
                        ->placeholder('-'),

                    TextEntry::make('contact_phone')
                        ->label('Contact Phone')
                        ->placeholder('-'),

                    TextEntry::make('created_at')
                        ->label('Created At')
                        ->dateTime('d M Y H:i'),

                    TextEntry::make('updated_at')
                        ->label('Updated At')
                        ->dateTime('d M Y H:i'),
                ]),
        ]);
    }
}
