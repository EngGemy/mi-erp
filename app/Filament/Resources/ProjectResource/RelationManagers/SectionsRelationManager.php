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

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';
    protected static ?string $title = 'الأقسام';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('اسم القسم')->required(),
            TextInput::make('sort')->label('الترتيب')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->emptyStateHeading('لا توجد أقسام')
            ->emptyStateDescription('تُنشأ تلقائياً مع المشروع من قالب كراون. إن كانت فارغة، استخدم «تحميل قالب كراون» من أعلى الصفحة.')
            ->columns([
                TextColumn::make('name')->label('القسم'),
                TextColumn::make('items_count')->counts('items')->label('عدد الأصناف'),
            ])
            ->headerActions([CreateAction::make()->label('إضافة قسم')])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }
}
