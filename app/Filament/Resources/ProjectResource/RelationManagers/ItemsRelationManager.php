<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Services\BomEngine;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'الأصناف والمعادلات';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات الصنف')->schema([
                TextInput::make('code')->label('الكود (لاتيني - يُشار إليه من معادلات أخرى)')
                    ->required()
                    ->rule('regex:/^[A-Za-z_][A-Za-z0-9_]*$/')
                    ->helperText('بدون مسافات. مثال: omega_3m'),
                TextInput::make('name')->label('بيان الصنف')->required(),
                Select::make('section_id')->label('القسم')
                    ->relationship('section', 'name')->searchable()->preload(),
                TextInput::make('piece_length')->label('طول القطعة')->numeric()->nullable(),
                TextInput::make('unit')->label('الوحدة')->nullable(),
                TextInput::make('sort')->label('الترتيب')->numeric()->default(0),
            ])->columns(3),

            Section::make('المعادلة')->schema([
                Group::make()->schema([
                    Placeholder::make('builder_hint')->label('')
                        ->content(new HtmlString(
                            '<div style="font-size:13px;color:#555">'
                            .'اكتب المعادلة بأسماء المتغيرات أو أكواد الأصناف. '
                            .'للإشارة لصنف آخر استخدم item(\'code\'). '
                            .'متاح: + - * / ( ) و الدوال ceil floor round min max abs.'
                            .'</div>'
                        )),
                    Textarea::make('formula')->label('معادلة الكمية الصافية')
                        ->rows(2)
                        ->placeholder('((cages + 2) * 2) * lines')
                        ->helperText("مثال إشارة لصنف: item('omega_3m') * 8")
                        ->rule(function () {
                            return function (string $attr, $value, \Closure $fail) {
                                if (blank($value)) return;
                                $project = $this->getOwnerRecord();
                                $err = app(BomEngine::class)->validateFormula($value, $project);
                                if ($err) $fail('خطأ في المعادلة: '.$err);
                            };
                        }),
                ])->columnSpanFull(),
            ]),

            Section::make('الزيادة / الهالك')->schema([
                Select::make('scrap_mode')->label('وضع الزيادة')
                    ->options([
                        'inherit' => 'وراثة من المشروع/النظام',
                        'percent' => 'نسبة مئوية مخصصة',
                        'fixed'   => 'رقم ثابت (+قطع)',
                        'formula' => 'معادلة',
                        'none'    => 'بدون زيادة',
                    ])
                    ->default('inherit')->live()->required(),
                TextInput::make('scrap_percent')->label('النسبة %')->numeric()
                    ->visible(fn (Get $g) => $g('scrap_mode') === 'percent'),
                TextInput::make('scrap_fixed')->label('الرقم الثابت')->numeric()
                    ->visible(fn (Get $g) => $g('scrap_mode') === 'fixed'),
                Textarea::make('scrap_formula')->label('معادلة الزيادة')->rows(1)
                    ->visible(fn (Get $g) => $g('scrap_mode') === 'formula'),
                Select::make('rounding')->label('التقريب')
                    ->options(['up' => 'لأعلى (تصنيع)', 'nearest' => 'الأقرب', 'none' => 'بدون'])
                    ->default('up')->required(),
                TextInput::make('required_override')
                    ->label('تجاوز المطلوب (للنواقص)')
                    ->numeric()->nullable()
                    ->helperText('فارغ = استخدم الإجمالي المحسوب تلقائياً'),
            ])->columns(3),

            Toggle::make('is_active')->label('نشط')->default(true),
            Textarea::make('notes')->label('ملاحظات')->rows(1)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->emptyStateHeading('لا توجد أصناف')
            ->emptyStateDescription('يُفترض أن تُضاف تلقائياً عند إنشاء المشروع. إن كانت فارغة، استخدم «تحميل قالب كراون» من أعلى الصفحة.')
            ->columns([
                TextColumn::make('code')->label('الكود')->searchable()->copyable(),
                TextColumn::make('name')->label('الصنف')->searchable()->wrap(),
                TextColumn::make('section.name')->label('القسم')->toggleable(),
                TextColumn::make('formula')->label('المعادلة')->limit(40)->tooltip(fn ($state) => $state),
                TextColumn::make('scrap_mode')->label('الزيادة')
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'inherit' => 'وراثة', 'percent' => 'نسبة', 'fixed' => 'ثابت',
                        'formula' => 'معادلة', 'none' => 'بدون', default => $s,
                    })->badge(),
            ])
            ->headerActions([CreateAction::make()->label('إضافة صنف')])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }
}
