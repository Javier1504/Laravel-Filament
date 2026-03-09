<?php

namespace App\Filament\Resources\Tickets\Tables;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Code')->sortable()->searchable()->copyable(),
                TextColumn::make('title')->label('Summary')->searchable()->limit(40),
                TextColumn::make('request_type')->label('Type')->badge()->formatStateUsing(fn ($state) => Ticket::requestTypeOptions()[$state] ?? $state),
                TextColumn::make('category.name')->label('Category')->placeholder('-'),
                TextColumn::make('priority')->label('Priority')->badge()->color(fn (string $state): string => match ($state) {
                    'low' => 'gray', 'medium' => 'warning', 'high' => 'danger', 'urgent' => 'danger', default => 'gray'
                })->formatStateUsing(fn ($state) => Ticket::priorityOptions()[$state] ?? $state),
                TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                    Ticket::STATUS_NEW => 'gray',
                    Ticket::STATUS_ASSIGNED => 'info',
                    Ticket::STATUS_ON_GOING => 'warning',
                    Ticket::STATUS_PENDING_REVIEW => 'primary',
                    Ticket::STATUS_RESOLVED => 'success',
                    Ticket::STATUS_CLOSED => 'gray',
                    default => 'gray',
                })->formatStateUsing(fn ($state) => Ticket::statusOptions()[$state] ?? $state),
                TextColumn::make('requester.name')->label('Requester')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('assignee.name')->label('Technician')->placeholder('Belum ada'),
                IconColumn::make('is_overdue')->label('Overdue')->boolean(),
                TextColumn::make('due_at')->label('Due')->dateTime('d M Y H:i')->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('request_type')->options(Ticket::requestTypeOptions()),
                SelectFilter::make('status')->options(Ticket::statusOptions()),
                SelectFilter::make('priority')->options(Ticket::priorityOptions()),
                Filter::make('overdue')
                    ->label('Overdue')
                    ->query(fn ($query) => $query
                        ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
                        ->whereNotNull('due_at')
                        ->where('due_at', '<', now())),
                Filter::make('my_requests')
                    ->label('Request saya')
                    ->query(fn ($query) => $query->where('requester_id', Auth::id())),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Ticket $record) => TicketResource::getUrl('view', ['record' => $record])),

                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (Ticket $record) => self::canAdmin())
                    ->url(fn (Ticket $record) => TicketResource::getUrl('edit', ['record' => $record])),

                Action::make('assign')
                    ->label('Assign Technician')
                    ->icon('heroicon-o-user-plus')
                    ->visible(fn () => self::canAdmin())
                    ->form([
                        \Filament\Forms\Components\Select::make('assignee_id')
                            ->label('Technician')
                            ->options(fn () => User::role('technician')->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->fillForm(fn (Ticket $record) => ['assignee_id' => $record->assignee_id])
                    ->action(function (Ticket $record, array $data): void {
                        $assignee = filled($data['assignee_id'] ?? null) ? User::find($data['assignee_id']) : null;
                        $record->assignTechnician($assignee, Auth::user());
                    }),

                Action::make('mark_ongoing')
                    ->label('On Going')
                    ->icon('heroicon-o-play')
                    ->visible(fn (Ticket $record) => self::canAdmin() && filled($record->assignee_id) && ! in_array($record->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED], true))
                    ->requiresConfirmation()
                    ->action(fn (Ticket $record) => $record->moveToOngoing(Auth::user())),

                Action::make('submit_progress')
                    ->label('Report Progress')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn (Ticket $record) => self::canAssignedTechnician($record))
                    ->form([
                        \Filament\Forms\Components\Textarea::make('summary')->required()->rows(3)->label('Ringkasan progress'),
                        \Filament\Forms\Components\Textarea::make('detail')->rows(5)->label('Detail pengerjaan'),
                    ])
                    ->action(fn (Ticket $record, array $data) => $record->submitProgress(Auth::user(), (string) $data['summary'], $data['detail'] ?? null)),

                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Ticket $record) => self::canAdmin() && $record->status === Ticket::STATUS_PENDING_REVIEW)
                    ->form([
                        \Filament\Forms\Components\Textarea::make('review_note')->label('Catatan admin')->rows(4),
                    ])
                    ->action(fn (Ticket $record, array $data) => $record->approveByAdmin(Auth::user(), $data['review_note'] ?? null)),

                Action::make('reject')
                    ->label('Reject')
                    ->color('warning')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn (Ticket $record) => self::canAdmin() && $record->status === Ticket::STATUS_PENDING_REVIEW)
                    ->form([
                        \Filament\Forms\Components\Textarea::make('review_note')->label('Alasan reject')->rows(4)->required(),
                    ])
                    ->action(fn (Ticket $record, array $data) => $record->rejectToOngoing(Auth::user(), (string) $data['review_note'])),

                DeleteAction::make()
                    ->visible(fn () => self::canAdmin()),
            ]);
    }

    private static function canAdmin(): bool
    {
        return (bool) Auth::user()?->hasRole('admin');
    }

    private static function canAssignedTechnician(Ticket $record): bool
    {
        $user = Auth::user();

        return (bool) $user
            && $user->hasRole('technician')
            && (int) $record->assignee_id === (int) $user->id;
    }
}
