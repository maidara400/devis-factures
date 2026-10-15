<?php

namespace App\Http\Resources;

use App\Models\Export;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Export
 */
class ExportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'filters' => $this->filters,
            'rows_count' => $this->rows_count,
            'error' => $this->error,
            'download_url' => $this->isCompleted() ? url("/api/v1/exports/{$this->id}/download") : null,
            'created_at' => $this->created_at,
            'completed_at' => $this->completed_at,
        ];
    }
}
