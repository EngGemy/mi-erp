<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use App\Services\CatalogApplyService;
use App\Services\CrownSettings;
use App\Support\ProjectDefaultVariables;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('crownReady')
                ->label('مشروع كراون جاهز')
                ->icon('heroicon-o-document-duplicate')
                ->color('success')
                ->modalHeading('إنشاء مشروع كراون جاهز')
                ->modalDescription('يُنشأ المشروع بمتغيرات كراون الافتراضية ويُسحب من الكتالوج المركزي (78 صنف).')
                ->modalSubmitActionLabel('إنشاء وفتح الحصر')
                ->form([
                    TextInput::make('name')
                        ->label('اسم المشروع')
                        ->required()
                        ->maxLength(255)
                        ->default('كراون - تسمين'),
                    TextInput::make('code')
                        ->label('كود المشروع')
                        ->required()
                        ->maxLength(255)
                        ->unique(Project::class, 'code')
                        ->default(fn () => 'CROWN-'.strtoupper(substr(uniqid(), -6))),
                ])
                ->action(function (array $data, ListProjects $livewire): void {
                    $defaults = CrownSettings::projectDefaults();
                    $project = Project::create([
                        'name'                  => $data['name'],
                        'code'                  => $data['code'],
                        'description'           => 'مشروع من قالب كراون',
                        'default_scrap_percent' => $defaults['default_scrap_percent'],
                        'units_multiplier'      => $defaults['units_multiplier'],
                        'is_active'             => true,
                    ]);

                    ProjectDefaultVariables::syncToProject($project, [
                        'tiers' => 4,
                        'lines' => 5,
                        'cages' => 118,
                        'cage'  => 1,
                    ]);

                    $stats = app(CatalogApplyService::class)->applyFromCatalog(
                        $project,
                        CatalogApplyService::MODE_REPLACE
                    );

                    Notification::make()
                        ->title('تم إنشاء مشروع كراون')
                        ->body("{$stats['sections']} أقسام، {$stats['items']} صنف — جاري فتح الحصر.")
                        ->success()
                        ->send();

                    $livewire->redirect(ViewBom::getUrl(['record' => $project]));
                }),

            CreateAction::make()->label('مشروع جديد'),
        ];
    }
}
