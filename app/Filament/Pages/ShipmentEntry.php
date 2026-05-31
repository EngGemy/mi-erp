<?php

namespace App\Filament\Pages;

use App\Models\Item;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ShipmentEntry extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'shipment-entry';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.shipment-entry';

    public ?Shipment $shipment = null;

    public ?array $data = [];

    public function getTitle(): string
    {
        if (! $this->shipment) {
            return 'إدخال أصناف الحمولة';
        }

        $date = $this->shipment->shipped_at?->format('Y-m-d');

        return 'إدخال أصناف الحمولة: '.$this->shipment->name.($date ? " ({$date})" : '');
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getSubheading(): ?string
    {
        if (! $this->shipment) {
            return null;
        }

        $project = $this->shipment->project;

        return 'المشروع: '.$project->name.' — كود: '.$project->code;
    }

    public function mount(): void
    {
        $id = request()->integer('shipment');
        $this->shipment = Shipment::with(['items', 'project'])->findOrFail($id);

        $rows = $this->shipment->items->map(fn ($si) => [
            'item_id'  => $si->item_id,
            'quantity' => $si->quantity,
        ])->toArray();

        if (empty($rows)) {
            $rows = [['item_id' => null, 'quantity' => 0]];
        }

        $this->form->fill(['rows' => $rows]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('rows')
                    ->label('أصناف الحمولة')
                    ->schema([
                        Select::make('item_id')
                            ->label('الصنف')
                            ->options(fn () => Item::query()
                                ->where('project_id', $this->shipment->project_id)
                                ->where('is_active', true)
                                ->orderBy('sort')
                                ->get()
                                ->mapWithKeys(fn ($i) => [$i->id => $i->name.' ('.$i->code.')']))
                            ->searchable()
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0)
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->addActionLabel('إضافة صف')
                    ->reorderable()
                    ->defaultItems(1)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $rows = collect($this->form->getState()['rows'] ?? []);
        $keepItemIds = $rows->pluck('item_id')->filter()->all();

        ShipmentItem::query()
            ->where('shipment_id', $this->shipment->id)
            ->whereNotIn('item_id', $keepItemIds ?: [0])
            ->delete();

        foreach ($rows as $row) {
            if (empty($row['item_id'])) {
                continue;
            }
            ShipmentItem::updateOrCreate(
                ['shipment_id' => $this->shipment->id, 'item_id' => $row['item_id']],
                ['quantity' => max(0, (float) ($row['quantity'] ?? 0))]
            );
        }

        Notification::make()->title('تم حفظ الحمولة بنجاح')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ الكل')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action(fn () => $this->save()),
            Action::make('back')
                ->label('رجوع للمشروع')
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->url(fn () => route('filament.admin.resources.projects.edit', [
                    'record' => $this->shipment->project_id,
                ])),
        ];
    }
}
