<?php

namespace App\Http\Requests;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');
        $required = $creating ? 'required' : 'sometimes';

        return [
            'type' => [$creating ? 'required' : 'prohibited', Rule::enum(DocumentType::class)],
            'client_id' => [$required, 'integer', Rule::exists('clients', 'id')->whereNull('deleted_at')],
            'issue_date' => [$required, 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => [$required, 'array', 'min:1', 'max:100'],
            'lines.*.article_id' => ['nullable', 'integer', Rule::exists('articles', 'id')->whereNull('deleted_at')],
            'lines.*.description' => ['required_without:lines.*.article_id', 'nullable', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'lines.*.unit_price' => ['required_without:lines.*.article_id', 'nullable', 'numeric', 'min:0', 'max:999999999999'],
            'lines.*.tax_rate' => ['required_without:lines.*.article_id', 'nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'client_id' => 'client',
            'issue_date' => 'date d\'émission',
            'due_date' => 'date d\'échéance',
            'lines.*.quantity' => 'quantité',
            'lines.*.unit_price' => 'prix unitaire',
            'lines.*.description' => 'désignation',
        ];
    }
}
