<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Document
 */
class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type->label(),
            'number' => $this->number,
            'status' => $this->status,
            'status_label' => $this->status->label($this->type),
            'is_overdue' => $this->isOverdue(),
            'issue_date' => $this->issue_date->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'notes' => $this->notes,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->tax_total,
            'total' => $this->total,
            'sent_at' => $this->sent_at,
            'paid_at' => $this->paid_at,
            'source_quote_id' => $this->source_quote_id,
            'client' => new ClientResource($this->whenLoaded('client')),
            'author' => new UserResource($this->whenLoaded('user')),
            'lines' => DocumentLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
