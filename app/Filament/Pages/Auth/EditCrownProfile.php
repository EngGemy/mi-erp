<?php

namespace App\Filament\Pages\Auth;

use App\Support\CrownThemePalettes;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class EditCrownProfile extends BaseEditProfile
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['theme_color'] = $this->getUser()->theme_color ?? '';
        $data['theme_mode'] = $this->getUser()->theme_mode ?? 'system';

        return parent::mutateFormDataBeforeFill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('theme_color', $data) && $data['theme_color'] === '') {
            $data['theme_color'] = null;
        }

        return parent::mutateFormDataBeforeSave($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('الحساب')->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]),
            Section::make('الهوية والمظهر')
                ->description('تفضيلاتك الشخصية — لا تؤثر على باقي المستخدمين')
                ->schema([
                    $this->getThemeColorFormComponent(),
                    $this->getThemeModeFormComponent(),
                ])
                ->columns(2),
        ]);
    }

    protected function getThemeColorFormComponent(): Component
    {
        return Select::make('theme_color')
            ->label('اللون الأساسي')
            ->options(CrownThemePalettes::selectOptions(includeSystemDefault: true))
            ->native(false)
            ->helperText('اختر «افتراضي النظام» لاتباع إعداد المدير');
    }

    protected function getThemeModeFormComponent(): Component
    {
        return Select::make('theme_mode')
            ->label('الوضع')
            ->options([
                'system' => 'تلقائي (حسب الجهاز)',
                'light'  => 'فاتح',
                'dark'   => 'داكن',
            ])
            ->required()
            ->native(false);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $result = parent::handleRecordUpdate($record, $data);

        Notification::make()
            ->title('تم حفظ التفضيلات')
            ->body('سيتم تطبيق اللون والوضع على جلستك.')
            ->success()
            ->send();

        return $result;
    }

    protected function getRedirectUrl(): ?string
    {
        return static::getUrl();
    }
}
