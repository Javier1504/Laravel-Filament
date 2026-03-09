<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! Auth::user()?->hasRole('admin')) {
            unset($data['status'], $data['assignee_id'], $data['requester_id']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->recordEvent(Auth::user(), 'updated');

        Notification::make()->title('Ticket berhasil diperbarui')->success()->send();
    }

    public function canEdit($record): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }
}
