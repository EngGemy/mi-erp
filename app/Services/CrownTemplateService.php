<?php

namespace App\Services;

use App\Models\Project;

/**
 * @deprecated استخدم CatalogApplyService — محفوظ للتوافق الخلفي.
 */
class CrownTemplateService
{
    public function applyTo(Project $project): array
    {
        return app(CatalogApplyService::class)->applyFromCatalog(
            $project,
            CatalogApplyService::MODE_REPLACE
        );
    }
}
