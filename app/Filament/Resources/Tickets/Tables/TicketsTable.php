<?php

namespace App\Filament\Resources\Tickets\Tables;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Filament\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
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
                TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable()
                    ->copyable(),

                TextColumn::make('title')
                    ->label('Summary')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('request_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'incident' => 'Incident',
                        'service_request' => 'Service',
                        'access_request' => 'Access',
                        'maintenance' => 'Maintenance',
                        default => (string) $state,
                    })
                    ->sortable(),

                TextColumn::make('asset_tag')
                    ->label('Asset')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('location')
                    ->label('Location')
                    ->placeholder('-')
                    ->limit(25)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                BadgeColumn::make('priority')
                    ->label('Priority')
                    ->colors([
                        'gray' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                        'danger' => 'urgent',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                        default => (string) $state,
                    })
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'open',
                        'info' => 'in_progress',
                        'success' => 'resolved',
                        'gray' => 'closed',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                        default => (string) $state,
                    })
                    ->sortable(),

                TextColumn::make('assignee.name')
                    ->label('Technician')
                    ->placeholder('-')
                    ->sortable(),

                IconColumn::make('is_overdue')
                    ->label('Overdue')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->sortable(),

                TextColumn::make('due_at')
                    ->label('Due')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('request_type')
                    ->label('Type')
                    ->options([
                        'incident' => 'Incident',
                        'service_request' => 'Service Request',
                        'access_request' => 'Access Request',
                        'maintenance' => 'Maintenance',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                    ]),

                SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),

                Filter::make('overdue')
                    ->label('Overdue')
                    ->query(fn ($query) => $query
                        ->whereNotIn('status', ['resolved', 'closed'])
                        ->whereNotNull('due_at')
                        ->where('due_at', '<', now())),

                Filter::make('my_requests')
                    ->label('My Requests')
                    ->query(fn ($query) => $query->where('assignee_id', Auth::id())),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Ticket $record) => TicketResource::getUrl('view', ['record' => $record])),

                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Ticket $record) => TicketResource::getUrl('edit', ['record' => $record])),

                Action::make('assign_to_me')
                    ->label('Assign to me')
                    ->icon('heroicon-o-user-plus')
                    ->visible(fn (Ticket $record) => $record->assignee_id === null)
                    ->action(fn (Ticket $record) => $record->update(['assignee_id' => Auth::id()])),

                Action::make('start')
                    ->label('Start')
                    ->icon('heroicon-o-play')
                    ->visible(fn (Ticket $record) => $record->status === 'open')
                    ->action(fn (Ticket $record) => $record->update(['status' => 'in_progress'])),

                Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check')
                    ->visible(fn (Ticket $record) => in_array($record->status, ['open', 'in_progress'], true))
                    ->action(fn (Ticket $record) => $record->update(['status' => 'resolved'])),

                Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->visible(fn (Ticket $record) => $record->status === 'resolved')
                    ->action(fn (Ticket $record) => $record->update(['status' => 'closed'])),
            ]);
    }
}
