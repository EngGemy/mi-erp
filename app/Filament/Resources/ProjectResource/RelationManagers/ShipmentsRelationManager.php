<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Filament\Pages\ShipmentEntry;
use App\Models\ShipmentItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'shipments';

    protected static ?string $title = 'الحمولات (التوريد)';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('اسم الحمولة')->required()
                ->placeholder('حموله 1'),
            DatePicker::make('shipped_at')->label('تاريخ التوريد'),
            TextInput::make('driver_name')->label('السائق'),
            TextInput::make('vehicle_no')->label('رقم السيارة'),
            TextInput::make('responsible')->label('المسؤول'),
            DateTimePicker::make('arrival_time')->label('موعد الوصول'),
            Textarea::make('notes')->label('ملاحظات')->columnSpanFull(),
            TextInput::make('sort')->label('الترتيب')->numeric()->default(0),
        ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withSum('items', 'quantity'))
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')->label('اسم الحمولة')->searchable()->sortable(),
                TextColumn::make('shipped_at')->label('التاريخ')->date('Y-m-d')->sortable(),
                TextColumn::make('driver_name')->label('السائق'),
                TextColumn::make('vehicle_no')->label('السيارة'),
                TextColumn::make('responsible')->label('المسؤول'),
                TextColumn::make('arrival_time')->label('الموعد')->dateTime('Y-m-d H:i'),
                TextColumn::make('items_count')->counts('items')->label('عدد الأصناف')->alignCenter(),
                TextColumn::make('items_sum_quantity')
                    ->label('إجمالي الكمية المُسلّمة')
                    ->numeric(decimalPlaces: 0)
                    ->alignCenter(),
                TextColumn::make('contribution_pct')
                    ->label('نسبة المساهمة')
                    ->state(function ($record, RelationManager $livewire): string {
                        $project = $livewire->getOwnerRecord();
                        $projectTotal = (float) ShipmentItem::query()
                            ->whereIn('shipment_id', $project->shipments()->pluck('id'))
                            ->sum('quantity');
                        $shipmentTotal = (float) ($record->items_sum_quantity ?? 0);

                        if ($projectTotal <= 0) {
                            return '—';
                        }

                        return round(($shipmentTotal / $projectTotal) * 100, 1).'%';
                    })
                    ->alignCenter(),
            ])
            ->headerActions([
                CreateAction::make()->label('إضافة حمولة'),
            ])
            ->actions([
                Action::make('items')
                    ->label('إدخال الأصناف')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('primary')
                    ->url(fn ($record) => ShipmentEntry::getUrl(['shipment' => $record->id])),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
