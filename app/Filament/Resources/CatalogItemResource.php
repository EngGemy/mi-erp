<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatalogItemResource\Pages;
use App\Filament\Resources\CatalogItemResource\RelationManagers;
use App\Models\CatalogItem;
use App\Models\Project;
use App\Services\BomEngine;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class CatalogItemResource extends Resource
{
    protected static ?string $model = CatalogItem::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'أصناف الكتالوج';

    protected static ?string $modelLabel = 'صنف كتالوج';

    protected static ?string $pluralModelLabel = 'أصناف الكتالوج';

    protected static string|\UnitEnum|null $navigationGroup = 'الكتالوج المركزي';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make('بيانات الصنف')->schema([
                TextInput::make('code')
                    ->label('الكود (لاتيني)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->rule('regex:/^[A-Za-z_][A-Za-z0-9_]*$/')
                    ->helperText('يُستخدم في item() و itemF()'),
                TextInput::make('name')->label('بيان الصنف')->required(),
                Select::make('catalog_section_id')
                    ->label('القسم')
                    ->relationship('section', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('piece_length')->label('طول القطعة')->numeric()->nullable(),
                TextInput::make('unit')->label('الوحدة')->nullable(),
                TextInput::make('sort')->label('الترتيب')->numeric()->default(0),
            ])->columns(3),

            FormSection::make('المعادلة')->schema([
                Textarea::make('formula')
                    ->label('معادلة الكمية الصافية')
                    ->rows(3)
                    ->placeholder('((cages + 2) * 2) * lines')
                    ->helperText(new HtmlString(
                        'الإشارة لصنف آخر: <code dir="ltr">item(\'code\')</code> للصافي (D)، '
                        .'<code dir="ltr">itemF(\'code\')</code> بالزيادة (F).'
                    ))
                    ->rule(function () {
                        return function (string $attribute, $value, \Closure $fail): void {
                            if (blank($value)) {
                                return;
                            }
                            $project = Project::where('code', 'CROWN-FATTEN')->first()
                                ?? Project::query()->has('variables')->first();
                            if (! $project) {
                                return;
                            }
                            $err = app(BomEngine::class)->validateFormula($value, $project);
                            if ($err) {
                                $fail('خطأ في المعادلة: '.$err);
                            }
                        };
                    })
                    ->columnSpanFull(),
            ]),

            FormSection::make('الزيادة / الهالك')->schema([
                Select::make('scrap_mode')
                    ->label('وضع الزيادة')
                    ->options([
                        'inherit' => 'وراثة من المشروع',
                        'percent' => 'نسبة مئوية',
                        'fixed'   => 'رقم ثابت',
                        'none'    => 'بدون زيادة',
                    ])
                    ->default('inherit')
                    ->live()
                    ->required(),
                TextInput::make('scrap_percent')
                    ->label('النسبة %')
                    ->numeric()
                    ->visible(fn (Get $get) => $get('scrap_mode') === 'percent'),
                TextInput::make('scrap_fixed')
                    ->label('الرقم الثابت')
                    ->numeric()
                    ->visible(fn (Get $get) => $get('scrap_mode') === 'fixed'),
                Select::make('rounding')
                    ->label('التقريب')
                    ->options([
                        'up'      => 'لأعلى (تصنيع)',
                        'nearest' => 'الأقرب',
                        'none'    => 'بدون',
                    ])
                    ->default('up')
                    ->required(),
                Toggle::make('is_active')->label('نشط')->default(true),
            ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('code')->label('الكود')->searchable()->copyable(),
                TextColumn::make('name')->label('بيان الصنف')->searchable()->wrap(),
                TextColumn::make('section.name')->label('القسم')->sortable(),
                TextColumn::make('formula')->label('المعادلة')->limit(36)->tooltip(fn ($state) => $state),
                TextColumn::make('scrap_mode')->label('الزيادة')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'inherit' => 'وراثة',
                        'percent' => 'نسبة',
                        'fixed'   => 'ثابت',
                        'none'    => 'بدون',
                        default   => $state,
                    })
                    ->badge(),
                IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemRecipesRelationManager::class, // @phpstan-ignore-line
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCatalogItems::route('/'),
            'create' => Pages\CreateCatalogItem::route('/create'),
            'edit'   => Pages\EditCatalogItem::route('/{record}/edit'),
        ];
    }
}
