<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use App\Services\CrownSettings;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'المشاريع';

    protected static ?string $modelLabel = 'مشروع';

    protected static ?string $pluralModelLabel = 'المشاريع';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make('بيانات المشروع')
                ->schema([
                    TextInput::make('name')
                        ->label('اسم المشروع')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('code')
                        ->label('الكود')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Textarea::make('description')
                        ->label('الوصف')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            FormSection::make('إعدادات الحصر')
                ->schema([
                    TextInput::make('default_scrap_percent')
                        ->label('نسبة الهالك الافتراضية للمشروع (%)')
                        ->numeric()
                        ->nullable()
                        ->default(fn () => CrownSettings::defaultScrapPercent())
                        ->helperText('القيمة الافتراضية من الإعدادات العامة'),
                    TextInput::make('units_multiplier')
                        ->label('مضاعف الإجمالي (عدد العنابر)')
                        ->numeric()
                        ->default(fn () => CrownSettings::defaultUnitsMultiplier())
                        ->required()
                        ->helperText('مثال: 2 = عنبرين (يقابل العمود H في إكسل)'),
                    Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),
                    Select::make('status')
                        ->label('حالة المشروع')
                        ->options([
                            'draft'        => 'مسودة',
                            'in_progress'  => 'قيد التنفيذ',
                            'delivered'    => 'مُسلّم',
                        ])
                        ->default('draft')
                        ->required(),
                ])
                ->columns(4),

            FormSection::make('المتغيرات الأساسية')
                ->description('قيم الحصر الأساسية — تُستخدم في جميع معادلات الأصناف')
                ->schema([
                    TextInput::make('var_tiers')
                        ->label('عدد الأدوار')
                        ->numeric()
                        ->required()
                        ->default(4)
                        ->minValue(0),
                    TextInput::make('var_lines')
                        ->label('عدد الخطوط')
                        ->numeric()
                        ->required()
                        ->default(5)
                        ->minValue(0),
                    TextInput::make('var_cages')
                        ->label('عدد العشوش أفقياً')
                        ->numeric()
                        ->required()
                        ->default(118)
                        ->minValue(0),
                    TextInput::make('var_cage')
                        ->label('العش')
                        ->numeric()
                        ->required()
                        ->default(1)
                        ->minValue(0),
                ])
                ->columns(4)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('المشروع')->searchable()->sortable(),
                TextColumn::make('code')->label('الكود')->searchable(),
                ViewColumn::make('progress_cached')
                    ->label('الإكتمال')
                    ->view('filament.tables.columns.progress-bar')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'in_progress' => 'قيد التنفيذ',
                        'delivered'   => 'مُسلّم',
                        default       => 'مسودة',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'delivered'   => 'success',
                        'in_progress' => 'warning',
                        default       => 'gray',
                    }),
                TextColumn::make('items_count')->counts('items')->label('عدد الأصناف'),
                TextColumn::make('units_multiplier')->label('المضاعف'),
                TextColumn::make('default_scrap_percent')->label('هالك %')->suffix('%'),
                IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->actions([
                Action::make('wbs')
                    ->label('WBS')
                    ->icon('heroicon-o-chart-bar')
                    ->color('success')
                    ->visible(fn () => auth()->user()?->can('View:ViewWbs') ?? false)
                    ->url(fn (Project $r) => Pages\ViewWbs::getUrl(['record' => $r])),
                Action::make('bom')
                    ->label('الحصر')
                    ->icon('heroicon-o-calculator')
                    ->visible(fn () => auth()->user()?->can('View:ViewBom') ?? false)
                    ->url(fn (Project $r) => Pages\ViewBom::getUrl(['record' => $r])),
                Action::make('shortage')
                    ->label('النواقص')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->visible(fn () => auth()->user()?->can('View:ViewShortage') ?? false)
                    ->url(fn (Project $r) => Pages\ViewShortage::getUrl(['record' => $r])),
                Action::make('shipments')
                    ->label('تقرير الحمولات')
                    ->icon('heroicon-o-truck')
                    ->color('info')
                    ->visible(fn () => auth()->user()?->can('View:ViewShipmentReport') ?? false)
                    ->url(fn (Project $r) => Pages\ViewShipmentReport::getUrl(['record' => $r])),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SectionsRelationManager::class,
            RelationManagers\ItemsRelationManager::class,
            RelationManagers\ShipmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'      => Pages\ListProjects::route('/'),
            'create'     => Pages\CreateProject::route('/create'),
            'edit'       => Pages\EditProject::route('/{record}/edit'),
            'wbs'        => Pages\ViewWbs::route('/{record}/wbs'),
            'bom'        => Pages\ViewBom::route('/{record}/bom'),
            'shortage'   => Pages\ViewShortage::route('/{record}/shortage'),
            'shipments'  => Pages\ViewShipmentReport::route('/{record}/shipments'),
        ];
    }
}
