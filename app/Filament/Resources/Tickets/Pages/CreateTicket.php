<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! Auth::user()?->hasRole('admin')) {
            $data['requester_id'] = Auth::id();
            $data['assignee_id'] = null;
            $data['status'] = Ticket::STATUS_NEW;
        } else {
            $data['status'] = filled($data['assignee_id'] ?? null)
                ? ($data['status'] ?? Ticket::STATUS_ASSIGNED)
                : ($data['status'] ?? Ticket::STATUS_NEW);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->recordEvent(Auth::user(), 'created', null, $this->record->status);

        if (filled($this->record->assignee_id) && $this->record->status === Ticket::STATUS_ASSIGNED) {
            $this->record->recordEvent(Auth::user(), 'assigned', Ticket::STATUS_NEW, Ticket::STATUS_ASSIGNED, [
                'to_assignee_id' => $this->record->assignee_id,
            ]);
        }

        Notification::make()->title('Ticket berhasil dibuat')->success()->send();
    }
}
