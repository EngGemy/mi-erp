<?php

namespace App\Services;

use App\Models\Project;

/**
 * خدمة النواقص (Shortage)
 * --------------------------------------------------------------
 * المطلوب (required) = required_override إن وُجد، وإلا الإجمالي المحسوب (total/H) من BomEngine.
 * المُسلّم (delivered) = مجموع كميات الصنف عبر كل الحمولات.
 * الناقص (shortage) = required - delivered.
 *   shortage > 0  → ناقص (لم يُسلّم بالكامل)
 *   shortage = 0  → مكتمل
 *   shortage < 0  → تسليم زائد (Over-delivered)
 */
class ShortageService
{
    public function __construct(protected BomEngine $engine) {}

    /**
     * @return array{
     *   shipments: array<int,array>,
     *   rows: array<int,array>,
     *   totals: array
     * }
     */
    public function build(Project $project, ?int $activeShipmentId = null): array
    {
        $project = $project->fresh([
            'variables', 'items.section', 'shipments.items',
        ]);

        $bom = collect($this->engine->calculate($project))->keyBy('code');
        $context = $this->loadDeliveryContext($project);

        $rows = [];
        $totals = ['required' => 0.0, 'delivered' => 0.0, 'remaining' => 0.0];

        foreach ($project->items()->where('is_active', true)->orderBy('sort')->get() as $item) {
            $computed = (float) ($bom->get($item->code)['total'] ?? 0);
            $required = $item->required_override !== null
                ? (float) $item->required_override
                : $computed;

            $delivered = (float) ($context['delivered_by_item'][$item->id] ?? 0);
            $metrics = $this->deliveryMetrics($required, $delivered);

            $activeQty = $activeShipmentId
                ? round((float) (($context['matrix_by_item'][$item->id][$activeShipmentId] ?? 0)), 2)
                : 0.0;

            $rows[] = array_merge([
                'item_id'     => $item->id,
                'code'        => $item->code,
                'name'        => $item->name,
                'section'     => $item->section?->name,
                'computed'    => round($computed, 2),
                'is_override' => $item->required_override !== null,
                'required'    => round($required, 2),
                'matrix'      => $context['matrix_by_item'][$item->id] ?? [],
                'active_qty'  => $activeQty,
            ], $metrics);

            $totals['required'] += $required;
            $totals['delivered'] += $delivered;
            $totals['remaining'] += $metrics['remaining'];
        }

        return [
            'shipments' => $context['shipments'],
            'rows'      => $rows,
            'totals'    => $this->finalizeTotals($totals),
        ];
    }

    /**
     * دمج التوريد مع صفوف الحصر الحية (معاينة المتغيرات في ViewBom).
     *
     * @param  array<int, array>  $bomRows
     * @return array{
     *   shipments: array<int,array>,
     *   shortage_by_item: array<int,array>,
     *   totals: array
     * }
     */
    public function enrichBomDelivery(Project $project, array $bomRows, ?int $activeShipmentId = null): array
    {
        $project = $project->loadMissing(['items', 'shipments.items']);
        $context = $this->loadDeliveryContext($project);
        $itemsByCode = $project->items->where('is_active', true)->keyBy('code');

        $shortageByItem = [];
        $totals = ['required' => 0.0, 'delivered' => 0.0, 'remaining' => 0.0];

        foreach ($bomRows as $row) {
            if (! empty($row['error'])) {
                continue;
            }

            $item = $itemsByCode->get($row['code'] ?? '');
            if (! $item) {
                continue;
            }

            $required = $item->required_override !== null
                ? (float) $item->required_override
                : (float) ($row['total'] ?? 0);

            $delivered = (float) ($context['delivered_by_item'][$item->id] ?? 0);
            $metrics = $this->deliveryMetrics($required, $delivered);

            $activeQty = $activeShipmentId
                ? round((float) (($context['matrix_by_item'][$item->id][$activeShipmentId] ?? 0)), 2)
                : 0.0;

            $shortageByItem[$item->id] = array_merge([
                'item_id'     => $item->id,
                'code'        => $item->code,
                'required'    => round($required, 2),
                'matrix'      => $context['matrix_by_item'][$item->id] ?? [],
                'active_qty'  => $activeQty,
            ], $metrics);

            $totals['required'] += $required;
            $totals['delivered'] += $delivered;
            $totals['remaining'] += $metrics['remaining'];
        }

        return [
            'shipments'        => $context['shipments'],
            'shortage_by_item' => $shortageByItem,
            'totals'           => $this->finalizeTotals($totals),
        ];
    }

    /**
     * @return array{
     *   shipments: array<int,array>,
     *   delivered_by_item: array<int,float>,
     *   matrix_by_item: array<int,array<int,float>>
     * }
     */
    protected function loadDeliveryContext(Project $project): array
    {
        $project->loadMissing(['shipments.items']);

        $deliveredByItem = [];
        $matrixByItem = [];

        foreach ($project->shipments as $shipment) {
            foreach ($shipment->items as $si) {
                $deliveredByItem[$si->item_id] = ($deliveredByItem[$si->item_id] ?? 0) + (float) $si->quantity;
                $matrixByItem[$si->item_id][$shipment->id] = (float) $si->quantity;
            }
        }

        $shipments = $project->shipments->map(fn ($s) => [
            'id'           => $s->id,
            'name'         => $s->name,
            'shipped_at'   => optional($s->shipped_at)->format('Y-m-d'),
            'driver_name'  => $s->driver_name,
            'vehicle_no'   => $s->vehicle_no,
            'responsible'  => $s->responsible,
            'arrival_time' => optional($s->arrival_time)->format('Y-m-d H:i'),
            'notes'        => $s->notes,
        ])->values()->all();

        return [
            'shipments'         => $shipments,
            'delivered_by_item' => $deliveredByItem,
            'matrix_by_item'    => $matrixByItem,
        ];
    }

    /**
     * @return array{
     *   delivered: float,
     *   shortage: float,
     *   remaining: float,
     *   is_over: bool,
     *   over_qty: float,
     *   pct: float,
     *   status: string
     * }
     */
    protected function deliveryMetrics(float $required, float $delivered): array
    {
        $shortage = $required - $delivered;

        return [
            'delivered' => round($delivered, 2),
            'shortage'  => round($shortage, 2),
            'remaining' => round(max(0, $shortage), 2),
            'is_over'   => $shortage < -0.001,
            'over_qty'  => round(max(0, $delivered - $required), 2),
            'pct'       => $required > 0 ? round(($delivered / $required) * 100, 1) : 0.0,
            'status'    => $this->status($shortage, $required, $delivered),
        ];
    }

    /**
     * @param  array{required: float, delivered: float, remaining: float}  $totals
     */
    protected function finalizeTotals(array $totals): array
    {
        $required = $totals['required'];
        $delivered = $totals['delivered'];

        return [
            'required'  => round($required, 2),
            'delivered' => round($delivered, 2),
            'remaining' => round($totals['remaining'], 2),
            'shortage'  => round($required - $delivered, 2),
            'pct'       => $required > 0 ? round(($delivered / $required) * 100, 1) : 0.0,
        ];
    }

    protected function status(float $shortage, float $required, float $delivered): string
    {
        if ($required <= 0) {
            return 'none';                 // بلا مرجع
        }
        if ($shortage < 0) {
            return 'over';                 // تسليم زائد
        }
        if (abs($shortage) < 0.001) {
            return 'complete';             // مكتمل
        }
        if ($delivered <= 0) {
            return 'open';                 // لم يبدأ التوريد
        }
        return 'partial';                  // جزئي
    }
}
