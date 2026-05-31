<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Project;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Services\BomEngine;
use App\Services\ShortageService;
use Illuminate\Console\Command;

class VerifyBomDelivery extends Command
{
    protected $signature = 'crown:verify-bom-delivery';

    protected $description = 'اختبار أعمدة التوريد في الحصر — leg_post 2424 / 2297 مُسلّم';

    public function handle(BomEngine $engine, ShortageService $shortage): int
    {
        $project = Project::query()->where('code', 'CROWN-FATTEN')->first();
        if (! $project) {
            $this->error('مشروع CROWN-FATTEN غير موجود.');

            return self::FAILURE;
        }

        $project = $project->fresh(['variables', 'items', 'shipments.items']);
        $leg = Item::query()->where('project_id', $project->id)->where('code', 'leg_post')->first();
        if (! $leg) {
            $this->error('صنف leg_post غير موجود.');

            return self::FAILURE;
        }

        $bomRows = $engine->calculate($project);
        $h = (float) (collect($bomRows)->firstWhere('code', 'leg_post')['total'] ?? 0);
        $okH = abs($h - 2424) <= 2;
        $this->line(sprintf('leg_post H = %s → %s', $h, $okH ? 'OK' : 'FAIL'));

        $shipment = $project->shipments()->first()
            ?? $project->shipments()->create(['name' => 'اختبار توريد', 'sort' => 0, 'shipped_at' => now()]);

        ShipmentItem::query()->where('item_id', $leg->id)->delete();
        ShipmentItem::create([
            'shipment_id' => $shipment->id,
            'item_id'     => $leg->id,
            'quantity'    => 2297,
        ]);

        $project = $project->fresh(['variables', 'items', 'shipments.items']);
        $bomRows = $engine->calculate($project);
        $data = $shortage->enrichBomDelivery($project, $bomRows);
        $row = $data['shortage_by_item'][$leg->id] ?? null;

        if (! $row) {
            $this->error('لم تُدمج بيانات leg_post.');

            return self::FAILURE;
        }

        $okDel = abs($row['delivered'] - 2297) < 0.01;
        $okRem = abs($row['remaining'] - 127) < 0.01;
        $okPct = abs($row['pct'] - 94.8) < 0.5 || abs($row['pct'] - 95) < 0.5;

        $this->line(sprintf('المُسلّم = %s → %s', $row['delivered'], $okDel ? 'OK' : 'FAIL'));
        $this->line(sprintf('المتبقي = %s → %s', $row['remaining'], $okRem ? 'OK' : 'FAIL'));
        $this->line(sprintf('نسبة التوريد = %s%% → %s', $row['pct'], $okPct ? 'OK' : 'FAIL'));

        ShipmentItem::query()->where('item_id', $leg->id)->delete();
        $project = $project->fresh(['shipments.items']);
        $dataZero = $shortage->enrichBomDelivery($project, $engine->calculate($project));
        $rowZero = $dataZero['shortage_by_item'][$leg->id];
        $okZero = abs($rowZero['delivered']) < 0.01 && abs($rowZero['remaining'] - $h) < 0.01;
        $this->line(sprintf('بدون تسليم: مُسلّم=0 متبقي=%s → %s', $rowZero['remaining'], $okZero ? 'OK' : 'FAIL'));

        $okShipCols = count($data['shipments']) >= 1;
        $this->line(sprintf('عدد الحمولات للأعمدة = %d → %s', count($data['shipments']), $okShipCols ? 'OK' : 'FAIL'));

        $allOk = $okH && $okDel && $okRem && $okPct && $okZero && $okShipCols;
        $this->info($allOk ? 'اختبار التوريد في الحصر نجح.' : 'فشل اختبار.');

        return $allOk ? self::SUCCESS : self::FAILURE;
    }
}
