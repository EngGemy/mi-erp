<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariablesRelationManager extends RelationManager
{
    protected static string $relationship = 'variables';
    protected static ?string $title = 'المتغيرات الأساسية';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')->label('المفتاح (لاتيني)')->required()
                ->rule('regex:/^[A-Za-z_][A-Za-z0-9_]*$/')
                ->helperText('يُستخدم في المعادلات. مثال: tiers, lines, cages'),
            TextInput::make('label')->label('الاسم بالعربية')->required()
                ->helperText('مثال: عدد الأدوار'),
            TextInput::make('value')->label('القيمة')->numeric()->required(),
            TextInput::make('unit')->label('الوحدة')->nullable(),
            TextInput::make('sort')->label('الترتيب')->numeric()->default(0),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')->defaultSort('sort')
            ->columns([
                TextColumn::make('label')->label('المتغير'),
                TextColumn::make('key')->label('المفتاح')->badge()->copyable(),
                TextColumn::make('value')->label('القيمة'),
                TextColumn::make('unit')->label('الوحدة'),
            ])
            ->headerActions([CreateAction::make()->label('إضافة متغير')])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }
}
