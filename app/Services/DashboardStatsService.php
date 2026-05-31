<?php

namespace App\Services;

use App\Models\FinishedReceipt;
use App\Models\MaterialRequest;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\RawMaterial;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\WorkOrder;
use App\Support\CrownAuthorization;

class DashboardStatsService
{
    public function __construct(
        protected ShortageService $shortage,
        protected PurchaseOrderService $purchaseOrders,
        protected StockService $stock,
    ) {}

    public function canViewProjects(): bool
    {
        $user = auth()->user();

        return $user && ($user->can('ViewAny:Project') || CrownAuthorization::isAdmin($user));
    }

    public function canViewInventory(): bool
    {
        $user = auth()->user();

        return $user && (
            CrownAuthorization::canManageInventory($user)
            || $user->can('ViewAny:StockBalance')
            || $user->can('ViewAny:StockMovement')
        );
    }

    public function canViewPurchasing(): bool
    {
        return CrownAuthorization::canManagePurchasing() || auth()->user()?->can('ViewAny:PurchaseOrder') ?? false;
    }

    /**
     * @return array{total: int, draft: int, in_progress: int, delivered: int, avg_progress: float}
     */
    public function projectSummary(): array
    {
        $counts = Project::query()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $total = (int) $counts->sum();
        $avg = (float) Project::avg('progress_cached');

        return [
            'total'        => $total,
            'draft'        => (int) ($counts[Project::STATUS_DRAFT] ?? 0),
            'in_progress'  => (int) ($counts[Project::STATUS_IN_PROGRESS] ?? 0),
            'delivered'    => (int) ($counts[Project::STATUS_DELIVERED] ?? 0),
            'avg_progress' => round($avg, 2),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, code: string, progress: float, status: string}>
     */
    public function projectsWithProgress(int $limit = 8): array
    {
        return Project::query()
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (Project $p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'code'     => $p->code,
                'progress' => (float) ($p->progress_cached ?? 0),
                'status'   => $p->status ?? Project::STATUS_DRAFT,
            ])
            ->all();
    }

    /**
     * @return array{total_shortage_lines: int, total_shortage_qty: float, top_items: array<int, array{code: string, name: string, shortage: float}>}
     */
    public function globalShortages(): array
    {
        $totalLines = 0;
        $totalQty = 0.0;
        $byCode = [];

        foreach (Project::where('is_active', true)->get() as $project) {
            $built = $this->shortage->build($project);
            foreach ($built['rows'] as $row) {
                $shortage = (float) ($row['shortage'] ?? 0);
                if ($shortage <= 0) {
                    continue;
                }
                $totalLines++;
                $totalQty += $shortage;
                $code = $row['code'];
                $byCode[$code] = [
                    'code'     => $code,
                    'name'     => $row['name'],
                    'shortage' => ($byCode[$code]['shortage'] ?? 0) + $shortage,
                ];
            }
        }

        $top = collect($byCode)->sortByDesc('shortage')->take(5)->values()->all();

        return [
            'total_shortage_lines' => $totalLines,
            'total_shortage_qty'   => round($totalQty, 2),
            'top_items'            => $top,
        ];
    }

    /**
     * @return array<int, array{code: string, name: string, qty: float, unit: string}>
     */
    public function lowestRawStock(int $limit = 5): array
    {
        $warehouse = $this->stock->getRawWarehouse();

        return StockBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('stockable_type', RawMaterial::class)
            ->with('stockable')
            ->orderBy('qty_on_hand')
            ->limit($limit)
            ->get()
            ->map(fn (StockBalance $b) => [
                'code' => $b->stockable?->code ?? '—',
                'name' => $b->stockable?->name ?? '—',
                'qty'  => (float) $b->qty_on_hand,
                'unit' => $b->stockable?->unit ?? '',
            ])
            ->all();
    }

    /**
     * @return array{material_requests: int, finished_receipts: int, purchase_orders: int}
     */
    public function pendingCounts(): array
    {
        return [
            'material_requests' => MaterialRequest::where('status', MaterialRequest::STATUS_PENDING)->count(),
            'finished_receipts' => FinishedReceipt::where('status', FinishedReceipt::STATUS_PENDING)->count(),
            'purchase_orders'   => PurchaseOrder::whereIn('status', [
                PurchaseOrder::STATUS_SENT,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ])->count(),
        ];
    }

    /**
     * @return array<int, array>
     */
    public function recentMovements(int $limit = 8): array
    {
        return StockMovement::query()
            ->with(['warehouse', 'stockable', 'user'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($m) => [
                'at'        => $m->created_at?->format('Y-m-d H:i'),
                'warehouse' => $m->warehouse?->name,
                'type'      => $m->type === 'in' ? 'وارد' : 'صادر',
                'item'      => $m->stockable?->name ?? '—',
                'qty'       => $m->qty,
            ])
            ->all();
    }

    /**
     * @return array<int, array>
     */
    public function recentWorkOrders(int $limit = 8): array
    {
        return WorkOrder::query()
            ->with('project')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($wo) => [
                'order_number' => $wo->order_number,
                'project'      => $wo->project?->name,
                'status'       => $wo->status,
                'at'           => $wo->created_at?->format('Y-m-d'),
            ])
            ->all();
    }
}
