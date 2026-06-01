<?php

namespace App\Filament\Pages;

use App\Services\CrownSettings;
use App\Support\CrownAuthorization;
use App\Support\CrownThemePalettes;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class ManageGeneralSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'الإعدادات العامة';

    protected static ?string $title = 'الإعدادات العامة';

    protected static string|\UnitEnum|null $navigationGroup = 'الإدارة';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'general-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return CrownAuthorization::isAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $logoPath = $this->migrateLegacyLogoToPublic(CrownSettings::get('logo_path'));

        $this->form->fill([
            'factory_name'             => CrownSettings::get('factory_name'),
            'logo_path'                => $logoPath,
            'default_scrap_percent'    => CrownSettings::defaultScrapPercent(),
            'default_units_multiplier' => CrownSettings::defaultUnitsMultiplier(),
            'currency'                 => CrownSettings::get('currency', 'EGP'),
            'default_rounding'         => CrownSettings::defaultRounding(),
            'default_theme_color'    => CrownSettings::get('default_theme_color', CrownThemePalettes::DEFAULT_KEY),
            'stages'                   => CrownSettings::stagesForForm(),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('الهوية والمظهر')
                ->description('الافتراضي لكل المستخدمين ما لم يخصّصوا لوناً في ملفهم الشخصي')
                ->schema([
                    TextInput::make('factory_name')->label('اسم النظام / المصنع')->required(),
                    FileUpload::make('logo_path')
                        ->label('الشعار (mi_logo)')
                        ->image()
                        ->disk('public')
                        ->directory('settings/logo')
                        ->visibility('public')
                        ->maxSize(2048),
                    Select::make('default_theme_color')
                        ->label('اللون الأساسي الافتراضي')
                        ->options(CrownThemePalettes::selectOptions())
                        ->required()
                        ->native(false)
                        ->helperText('مجموعات ألوان هادئة — بدون اختيار حر'),
                    TextInput::make('currency')->label('العملة')->default('EGP')->maxLength(8),
                ])
                ->columns(2),

            Section::make('افتراضيات الحصر')->schema([
                TextInput::make('default_scrap_percent')
                    ->label('نسبة الهالك الافتراضية (%)')
                    ->numeric()
                    ->required(),
                TextInput::make('default_units_multiplier')
                    ->label('مضاعف الإجمالي الافتراضي')
                    ->numeric()
                    ->required()
                    ->minValue(0.01),
                Select::make('default_rounding')
                    ->label('التقريب الافتراضي')
                    ->options([
                        'up'      => 'تقريب لأعلى',
                        'nearest' => 'أقرب عدد',
                        'none'    => 'بدون تقريب',
                    ])
                    ->required(),
            ])->columns(3),

            Section::make('مراحل WBS وأوزانها')
                ->description('تصنيع / توريد / تركيب')
                ->schema([
                    Repeater::make('stages')
                        ->label('')
                        ->schema([
                            TextInput::make('id')->hidden(),
                            TextInput::make('name')->label('المرحلة')->required(),
                            TextInput::make('sort')->label('الترتيب')->numeric()->default(0),
                            TextInput::make('weight')->label('الوزن')->numeric()->required()->minValue(0),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('إضافة مرحلة'),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $logo = $data['logo_path'] ?? null;
        if (is_array($logo)) {
            $logo = $logo[0] ?? null;
        }
        $logo = $this->migrateLegacyLogoToPublic(is_string($logo) ? $logo : null);

        CrownSettings::setMany([
            'factory_name'             => $data['factory_name'],
            'logo_path'                => $logo,
            'default_scrap_percent'    => (float) $data['default_scrap_percent'],
            'default_units_multiplier' => (float) $data['default_units_multiplier'],
            'currency'                 => $data['currency'] ?? 'EGP',
            'default_rounding'         => $data['default_rounding'] ?? 'up',
            'default_theme_color'    => $data['default_theme_color'] ?? CrownThemePalettes::DEFAULT_KEY,
        ]);

        if (! empty($data['stages'])) {
            CrownSettings::syncStages($data['stages']);
        }

        Notification::make()->title('تم حفظ الإعدادات')->success()->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFormContentComponent(),
        ]);
    }

    /**
     * Move logos uploaded to the private disk before ->disk('public') was configured.
     */
    protected function migrateLegacyLogoToPublic(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return $path;
        }

        if (! Storage::disk('local')->exists($path)) {
            return $path;
        }

        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, Storage::disk('local')->get($path));
        }

        Storage::disk('local')->delete($path);
        CrownSettings::set('logo_path', $path);

        return $path;
    }

    protected function getFormContentComponent(): Form
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('general-settings-form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label('حفظ الإعدادات')
                        ->submit('save'),
                ]),
            ]);
    }
}
