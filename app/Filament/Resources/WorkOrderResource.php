<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkOrderResource\Pages;
use App\Models\CatalogItem;
use App\Models\Project;
use App\Models\WorkOrder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'أذونات الإنتاج';

    protected static ?string $modelLabel = 'إذن إنتاج';

    protected static ?string $pluralModelLabel = 'أذونات الإنتاج';

    protected static string|\UnitEnum|null $navigationGroup = 'الإنتاج';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make('الإذن')->schema([
                Select::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (?WorkOrder $record) => $record && $record->status !== WorkOrder::STATUS_DRAFT),
                TextInput::make('order_number')
                    ->label('رقم الإذن')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'WO-'.now()->format('ymd-His')),
                Textarea::make('notes')->label('ملاحظات')->columnSpanFull(),
            ])->columns(2),

            FormSection::make('أصناف الإنتاج')->schema([
                Repeater::make('items')
                    ->label('')
                    ->relationship()
                    ->schema([
                        Select::make('catalog_item_id')
                            ->label('الصنف التام')
                            ->options(fn () => CatalogItem::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('qty_ordered')
                            ->label('الكمية المطلوبة')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                        TextInput::make('qty_produced')
                            ->label('المُنتج')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                    ])
                    ->columns(3)
                    ->defaultItems(1)
                    ->addActionLabel('إضافة صنف')
                    ->disabled(fn (?WorkOrder $record) => $record && $record->status !== WorkOrder::STATUS_DRAFT),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')->label('الإذن')->searchable(),
                TextColumn::make('project.name')->label('المشروع'),
                TextColumn::make('status')->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'draft' => 'مسودة', 'issued' => 'صُدر', 'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل', 'cancelled' => 'ملغى', default => $s,
                    }),
                TextColumn::make('materialRequest.status')->label('طلب الخام')->badge()->placeholder('—'),
                TextColumn::make('created_at')->label('التاريخ')->dateTime('Y-m-d'),
            ])
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWorkOrders::route('/'),
            'create' => Pages\CreateWorkOrder::route('/create'),
            'edit'   => Pages\EditWorkOrder::route('/{record}/edit'),
        ];
    }
}
