<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assign')
                ->label('Assign Technician')
                ->visible(fn () => Auth::user()?->hasRole('admin'))
                ->form([
                    \Filament\Forms\Components\Select::make('assignee_id')
                        ->label('Technician')
                        ->options(fn () => User::role('technician')->orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->preload()
                        ->nullable(),
                ])
                ->fillForm(fn () => ['assignee_id' => $this->record->assignee_id])
                ->action(function (array $data): void {
                    $assignee = filled($data['assignee_id'] ?? null) ? User::find($data['assignee_id']) : null;
                    $this->record->assignTechnician($assignee, Auth::user());
                    $this->refreshFormData(['assignee_id', 'status']);
                    Notification::make()->title('Technician berhasil diassign')->success()->send();
                }),

            Action::make('report_progress')
                ->label('Report Progress')
                ->visible(fn () => Auth::user()?->hasRole('technician') && (int) $this->record->assignee_id === (int) Auth::id())
                ->form([
                    \Filament\Forms\Components\Textarea::make('summary')->required()->rows(3),
                    \Filament\Forms\Components\Textarea::make('detail')->rows(5),
                ])
                ->action(function (array $data): void {
                    $this->record->submitProgress(Auth::user(), (string) $data['summary'], $data['detail'] ?? null);
                    $this->refreshFormData(['status']);
                    Notification::make()->title('Progress berhasil dikirim')->success()->send();
                }),

            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->visible(fn () => Auth::user()?->hasRole('admin') && $this->record->status === Ticket::STATUS_PENDING_REVIEW)
                ->form([
                    \Filament\Forms\Components\Textarea::make('review_note')->rows(4),
                ])
                ->action(function (array $data): void {
                    $this->record->approveByAdmin(Auth::user(), $data['review_note'] ?? null);
                    $this->refreshFormData(['status', 'resolved_at']);
                    Notification::make()->title('Ticket di-approve')->success()->send();
                }),

            Action::make('reject')
                ->label('Reject')
                ->color('warning')
                ->visible(fn () => Auth::user()?->hasRole('admin') && $this->record->status === Ticket::STATUS_PENDING_REVIEW)
                ->form([
                    \Filament\Forms\Components\Textarea::make('review_note')->required()->rows(4),
                ])
                ->action(function (array $data): void {
                    $this->record->rejectToOngoing(Auth::user(), (string) $data['review_note']);
                    $this->refreshFormData(['status']);
                    Notification::make()->title('Ticket dikembalikan ke on going')->warning()->send();
                }),

            Action::make('mark_ongoing')
                ->label('On Going')
                ->visible(fn () => Auth::user()?->hasRole('admin') && filled($this->record->assignee_id) && ! in_array($this->record->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED], true))
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->moveToOngoing(Auth::user());
                    $this->refreshFormData(['status']);
                    Notification::make()->title('Ticket dipindah ke on going')->success()->send();
                }),

            Action::make('edit')
                ->url(fn () => TicketResource::getUrl('edit', ['record' => $this->record]))
                ->visible(fn () => Auth::user()?->hasRole('admin')),

            DeleteAction::make()->visible(fn () => Auth::user()?->hasRole('admin')),
        ];
    }
}
