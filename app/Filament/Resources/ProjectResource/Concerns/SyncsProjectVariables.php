<?php

namespace App\Filament\Resources\ProjectResource\Concerns;

use App\Support\ProjectDefaultVariables;

trait SyncsProjectVariables
{
    /** @var array<string, float> */
    protected array $pendingVariableValues = [];

    protected function extractPendingVariables(array $data): array
    {
        $this->pendingVariableValues = ProjectDefaultVariables::extractFromForm($data);

        return ProjectDefaultVariables::stripFromForm($data);
    }

    protected function persistProjectVariables(): void
    {
        ProjectDefaultVariables::syncToProject($this->record, $this->pendingVariableValues);
    }
}
