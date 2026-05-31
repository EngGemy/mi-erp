<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ShipmentItem;

class ShipmentReportService
{
    public function __construct(protected BomEngine $engine) {}

    /**
     * @return array{
     *   shipments: array<int, array>,
     *   project_total_delivered: float
     * }
     */
    public function build(Project $project): array
    {
        $project = $project->fresh(['shipments.items.item', 'variables', 'items']);

        $projectTotalDelivered = (float) ShipmentItem::query()
            ->whereIn('shipment_id', $project->shipments->pluck('id'))
            ->sum('quantity');

        $shipments = $project->shipments->sortBy('sort')->map(function ($sh) use ($projectTotalDelivered) {
            $items = $sh->items->map(fn ($si) => [
                'item_id'  => $si->item_id,
                'code'     => $si->item?->code,
                'name'     => $si->item?->name,
                'quantity' => (float) $si->quantity,
            ])->values()->all();

            $totalQty = (float) $sh->items->sum('quantity');
            $itemsCount = $sh->items->count();

            return [
                'id'              => $sh->id,
                'name'            => $sh->name,
                'shipped_at'      => optional($sh->shipped_at)->format('Y-m-d'),
                'driver_name'     => $sh->driver_name,
                'vehicle_no'      => $sh->vehicle_no,
                'responsible'     => $sh->responsible,
                'arrival_time'    => optional($sh->arrival_time)->format('Y-m-d H:i'),
                'notes'           => $sh->notes,
                'items_count'     => $itemsCount,
                'total_qty'       => round($totalQty, 2),
                'contribution_pct'=> $projectTotalDelivered > 0
                    ? round(($totalQty / $projectTotalDelivered) * 100, 1)
                    : 0.0,
                'items'           => $items,
            ];
        })->values()->all();

        return [
            'shipments'               => $shipments,
            'project_total_delivered' => round($projectTotalDelivered, 2),
        ];
    }
}
