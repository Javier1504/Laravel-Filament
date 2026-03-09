<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Category')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('slug', Str::slug((string) $state));
                        }),

                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(140)
                        ->helperText('Auto dibuat dari Name, bisa kamu ubah.'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull()
                        ->nullable(),
                ]),
        ]);
    }
}