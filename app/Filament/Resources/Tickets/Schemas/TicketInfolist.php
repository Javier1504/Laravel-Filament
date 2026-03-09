<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Models\Ticket;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Overview')
                ->columns(2)
                ->schema([
                    TextEntry::make('code')->label('Code')->copyable(),
                    TextEntry::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => Ticket::statusOptions()[$state] ?? $state),
                    TextEntry::make('title')->label('Summary')->columnSpanFull(),
                    TextEntry::make('description')->label('Description')->placeholder('-')->columnSpanFull(),
                    TextEntry::make('category.name')->label('Category')->placeholder('-'),
                    TextEntry::make('priority')->label('Priority')->badge()->formatStateUsing(fn ($state) => Ticket::priorityOptions()[$state] ?? $state),
                    TextEntry::make('requester.name')->label('Requester')->placeholder('-'),
                    TextEntry::make('assignee.name')->label('Technician')->placeholder('Belum diassign'),
                    TextEntry::make('asset_tag')->label('Asset')->placeholder('-'),
                    TextEntry::make('location')->label('Location')->placeholder('-'),
                    TextEntry::make('due_at')->label('Due At')->dateTime('d M Y H:i')->placeholder('-'),
                    TextEntry::make('created_at')->label('Created')->dateTime('d M Y H:i'),
                ]),

            Section::make('Notes / Progress / Review')
                ->schema([
                    RepeatableEntry::make('visible_comments')
                        ->label('')
                        ->state(function (Ticket $record): array {
                            $viewer = Auth::user();
                            if (! $viewer) {
                                return [];
                            }

                            return $record->visibleCommentsFor($viewer)
                                ->get()
                                ->map(fn ($comment) => [
                                    'actor' => $comment->user?->name ?? $comment->user?->email ?? 'Unknown',
                                    'comment_type' => match ($comment->comment_type) {
                                        Ticket::COMMENT_PROGRESS => 'Progress Report',
                                        Ticket::COMMENT_REVIEW => 'Admin Review',
                                        Ticket::COMMENT_REPLY => 'Reply',
                                        default => 'Note',
                                    },
                                    'visibility' => $comment->is_internal ? 'Internal' : 'Visible to requester',
                                    'body' => $comment->body,
                                    'created_at' => optional($comment->created_at)?->format('d M Y H:i'),
                                ])
                                ->toArray();
                        })
                        ->schema([
                            TextEntry::make('actor')->label('By'),
                            TextEntry::make('comment_type')->label('Type')->badge(),
                            TextEntry::make('visibility')->label('Visibility')->badge(),
                            TextEntry::make('body')->label('Body')->columnSpanFull(),
                            TextEntry::make('created_at')->label('Created'),
                        ])
                        ->columns(2),
                ]),

            Section::make('Activity Timeline')
                ->schema([
                    RepeatableEntry::make('activity_events')
                        ->label('')
                        ->state(function (Ticket $record): array {
                            return $record->events()
                                ->with('actor')
                                ->get()
                                ->map(fn ($event) => [
                                    'event_type' => match ($event->event_type) {
                                        'created' => 'Ticket dibuat',
                                        'assigned' => 'Ticket diassign',
                                        'updated' => 'Ticket diperbarui',
                                        'status_changed' => 'Status diubah',
                                        'progress_submitted' => 'Progress dikirim ke admin',
                                        'technician_note_added' => 'Technician menambahkan note',
                                        'comment_added' => 'Komentar ditambahkan',
                                        default => $event->event_type,
                                    },
                                    'actor' => $event->actor?->name ?? $event->actor?->email ?? 'System',
                                    'status_flow' => trim(($event->from_status ? (Ticket::statusOptions()[$event->from_status] ?? $event->from_status) : '-') . ' → ' . ($event->to_status ? (Ticket::statusOptions()[$event->to_status] ?? $event->to_status) : '-')),
                                    'created_at' => optional($event->created_at)?->format('d M Y H:i'),
                                ])
                                ->toArray();
                        })
                        ->schema([
                            TextEntry::make('event_type')->label('Event')->badge(),
                            TextEntry::make('actor')->label('Actor'),
                            TextEntry::make('status_flow')->label('Status'),
                            TextEntry::make('created_at')->label('Time'),
                        ])
                        ->columns(2),
                ]),
        ]);
    }
}
