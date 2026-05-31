<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ShipmentReportService;
use Illuminate\View\View;

class ShipmentReportPrintController extends Controller
{
    public function __invoke(Project $project): View
    {
        $data = app(ShipmentReportService::class)->build($project);

        return view('reports.shipment-report-print', [
            'project' => $project,
            'shipments' => $data['shipments'],
            'projectTotalDelivered' => $data['project_total_delivered'],
        ]);
    }
}
