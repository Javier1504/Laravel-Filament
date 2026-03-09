<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Category')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')->label('Name'),
                    TextEntry::make('slug')->label('Slug'),

                    IconEntry::make('is_active')
                        ->label('Active')
                        ->boolean()
                        ->columnSpanFull(),

                    TextEntry::make('description')
                        ->label('Description')
                        ->placeholder('-')
                        ->columnSpanFull(),

                    TextEntry::make('created_at')
                        ->label('Created')
                        ->dateTime('d M Y H:i')
                        ->placeholder('-'),

                    TextEntry::make('updated_at')
                        ->label('Updated')
                        ->dateTime('d M Y H:i')
                        ->placeholder('-'),
                ]),
        ]);
    }
}