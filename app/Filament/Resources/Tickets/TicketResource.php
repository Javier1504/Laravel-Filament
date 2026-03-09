<?php

namespace App\Filament\Resources\Tickets;

use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use App\Filament\Resources\Tickets\Schemas\TicketForm;
use App\Filament\Resources\Tickets\Schemas\TicketInfolist;
use App\Filament\Resources\Tickets\Tables\TicketsTable;
use App\Models\Ticket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Helpdesk';
    protected static ?string $navigationLabel = 'IT Support Requests';
    protected static ?string $modelLabel = 'IT Support Request';
    protected static ?string $pluralModelLabel = 'IT Support Requests';
    protected static ?string $recordTitleAttribute = 'code';

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return (bool) $user && ($user->hasRole('admin') || $user->hasRole('user'));
    }

    public static function canDelete($record): bool
    {
        return (bool) Auth::user()?->hasRole('admin');
    }

    public static function canDeleteAny(): bool
    {
        return (bool) Auth::user()?->hasRole('admin');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('technician') && ! $user->hasRole('user')) {
            return $query->where('assignee_id', $user->id);
        }

        if ($user->hasRole('user') && ! $user->hasRole('technician')) {
            return $query->where('requester_id', $user->id);
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('requester_id', $user->id)
              ->orWhere('assignee_id', $user->id);
        });
    }

    public static function form(Schema $schema): Schema
    {
        return TicketForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TicketsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'view' => ViewTicket::route('/{record}'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }
}
