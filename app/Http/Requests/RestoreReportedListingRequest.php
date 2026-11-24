<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestoreReportedListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('restoreListing', $this->route('report')) ?? false;
    }

    public function rules(): array
    {
        return [
            'resolution_notes' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'resolution_notes.required' => 'Explique por que o anúncio está sendo restaurado.',
            'resolution_notes.min' => 'O motivo da restauração deve ter pelo menos 10 caracteres.',
        ];
    }
}
