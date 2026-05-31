<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Exports\BomExport;
use App\Filament\Concerns\AuthorizesCrownPage;
use App\Filament\Concerns\ManagesActiveShipment;
use App\Filament\Resources\ProjectResource;
use App\Imports\BomImport;
use App\Models\Project;
use App\Models\ProjectVariable;
use App\Services\BomEngine;
use App\Services\ShortageService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Computed;
use Maatwebsite\Excel\Facades\Excel;

class ViewBom extends Page
{
    use AuthorizesCrownPage;
    use ManagesActiveShipment;

    protected static function crownPagePermission(): ?string
    {
        return 'View:ViewBom';
    }

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.pages.view-bom';

    public function getTitle(): string
    {
        return 'الحصر الأوتوماتيكي';
    }

    public Project $record;

    /** قيم المتغيرات الحية: variable_id => value */
    public array $varValues = [];

    /** القيم المحفوظة في قاعدة البيانات */
    public array $savedVarValues = [];

    public float $unitsMultiplier = 1;

    public float $savedUnitsMultiplier = 1;

    /** نتائج الحصر */
    public array $rows = [];

    public array $errors = [];

    public bool $showFormulas = false;

    public bool $showShipmentCols = false;

    /** إجماليات التوريد (مطلوب / مُسلّم / متبقي / %) */
    public array $deliveryTotals = [
        'required'  => 0,
        'delivered' => 0,
        'remaining' => 0,
        'pct'       => 0,
    ];

    public function toggleFormulas(): void
    {
        $this->showFormulas = ! $this->showFormulas;
    }

    public function toggleShipmentCols(): void
    {
        $this->showShipmentCols = ! $this->showShipmentCols;
    }

    protected function reloadShipmentContext(): void
    {
        $data = app(ShortageService::class)->enrichBomDelivery(
            $this->record,
            $this->rows,
            $this->activeShipmentId
        );

        $this->shipments = $data['shipments'];
        $this->shortageByItem = $data['shortage_by_item'];
        $this->deliveryTotals = $data['totals'];
    }

    public function mount(Project $record): void
    {
        $this->record = $record->load('variables');
        $this->syncVarValuesFromRecord();
        $this->savedVarValues = $this->varValues;
        $this->unitsMultiplier = (float) $this->record->units_multiplier;
        $this->savedUnitsMultiplier = $this->unitsMultiplier;
        $this->initActiveShipment();
        $this->recalculate();
    }

    protected function syncVarValuesFromRecord(): void
    {
        $this->varValues = [];
        foreach ($this->record->variables as $v) {
            $this->varValues[$v->id] = (float) $v->value;
        }
    }

    #[Computed]
    public function hasUnsavedChanges(): bool
    {
        foreach ($this->savedVarValues as $id => $val) {
            $current = $this->varValues[$id] ?? $val;
            if (abs($current - $val) > 0.000001) {
                return true;
            }
        }

        return abs($this->unitsMultiplier - $this->savedUnitsMultiplier) > 0.000001;
    }

    public function previewVariable(int $variableId, $value): void
    {
        if (! array_key_exists($variableId, $this->varValues)) {
            return;
        }

        $this->varValues[$variableId] = is_numeric($value) ? (float) $value : 0.0;
        $this->recalculate();
    }

    public function previewUnitsMultiplier($value): void
    {
        $this->unitsMultiplier = max(0.01, is_numeric($value) ? (float) $value : 1.0);
        $this->recalculate();
    }

    public function confirmSave(): void
    {
        foreach ($this->record->variables as $v) {
            if (isset($this->varValues[$v->id])) {
                ProjectVariable::query()
                    ->where('id', $v->id)
                    ->where('project_id', $this->record->id)
                    ->update(['value' => $this->varValues[$v->id]]);
            }
        }

        $this->record->update(['units_multiplier' => $this->unitsMultiplier]);
        $this->savedVarValues = $this->varValues;
        $this->savedUnitsMultiplier = $this->unitsMultiplier;
        $this->recalculate();

        Notification::make()->title('تم حفظ المتغيرات')->success()->send();
    }

    public function discardChanges(): void
    {
        $this->varValues = $this->savedVarValues;
        $this->unitsMultiplier = $this->savedUnitsMultiplier;
        $this->recalculate();

        Notification::make()->title('تم التراجع عن التغييرات')->info()->send();
    }

    public function recalculate(): void
    {
        $this->record = $this->record->fresh(['variables', 'items.section']);

        foreach ($this->record->variables as $v) {
            if (array_key_exists($v->id, $this->varValues)) {
                $v->value = $this->varValues[$v->id];
            }
        }

        $this->record->units_multiplier = $this->unitsMultiplier;

        $engine = app(BomEngine::class);
        $this->rows = $engine->calculate($this->record);
        $this->errors = collect($this->rows)->filter(fn ($r) => $r['error'])->values()->all();
        $this->reloadShipmentContext();
    }

    protected function afterShipmentContextReloaded(): void
    {
        $this->reloadShipmentContext();
    }

    protected function getHeaderActions(): array
    {
        $canEdit = auth()->user()?->can('Update:Project') ?? false;

        return [
            Action::make('export')
                ->visible($canEdit)
                ->label('تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return Excel::download(
                        new BomExport($this->record),
                        'bom_'.$this->record->code.'.xlsx'
                    );
                }),

            Action::make('import')
                ->visible($canEdit)
                ->label('استيراد Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    FileUpload::make('file')
                        ->label('ملف Excel (أعمدة: code, name, section, length, formula, scrap_mode, scrap_percent, scrap_fixed, rounding)')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required()
                        ->disk('local')->directory('imports'),
                ])
                ->action(function (array $data) {
                    $path = storage_path('app/'.$data['file']);
                    Excel::import(new BomImport($this->record), $path);
                    $this->syncVarValuesFromRecord();
                    $this->savedVarValues = $this->varValues;
                    $this->unitsMultiplier = (float) $this->record->fresh()->units_multiplier;
                    $this->savedUnitsMultiplier = $this->unitsMultiplier;
                    $this->recalculate();
                    Notification::make()->title('تم الاستيراد بنجاح')->success()->send();
                }),
        ];
    }
}
