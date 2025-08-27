<?php

namespace App\Http\Requests;

use App\Models\Area;
use App\Models\User;
use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentDerivationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'document_id' => ['required', 'exists:documents,id'],
            'to_area_id' => [
                'required',
                'exists:areas,id',
                'different:from_area_id',
                function ($attribute, $value, $fail) {
                    $area = Area::find($value);
                    if (!$area || !$area->status) {
                        $fail('El área seleccionada no está activa.');
                    }
                }
            ],
            'assigned_to' => [
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->to_area_id) {
                        $user = User::find($value);
                        if ($user && $user->area_id != $this->to_area_id) {
                            $fail('El usuario seleccionado no pertenece al área destino.');
                        }
                    }
                }
            ],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'movement_type' => ['required', Rule::in(['information', 'action', 'approval', 'review', 'archive'])],
            'due_date' => [
                'nullable',
                'date',
                'after:now',
                function ($attribute, $value, $fail) {
                    if ($value && $this->priority === 'urgent') {
                        $dueDate = \Carbon\Carbon::parse($value);
                        if ($dueDate->diffInHours(now()) > 24) {
                            $fail('Los documentos urgentes deben tener una fecha límite dentro de las próximas 24 horas.');
                        }
                    }
                }
            ],
            'requires_response' => ['boolean'],
            'observations' => ['nullable', 'string', 'max:1000'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.file_path' => ['required_with:attachments', 'string'],
            'attachments.*.attachment_type' => ['required_with:attachments', Rule::in(['response', 'annex', 'support', 'other'])],
            'attachments.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'to_area_id.different' => 'No puede derivar un documento a la misma área de origen.',
            'due_date.after' => 'La fecha límite debe ser posterior a la fecha actual.',
            'assigned_to.exists' => 'El usuario seleccionado no existe.',
            'attachments.*.file_path.required_with' => 'Debe seleccionar un archivo.',
            'attachments.*.attachment_type.required_with' => 'Debe especificar el tipo de archivo.',
        ];
    }

    protected function prepareForValidation()
    {
        // Obtener el área actual del documento
        if ($this->document_id) {
            $document = Document::find($this->document_id);
            if ($document) {
                $this->merge([
                    'from_area_id' => $document->current_area_id
                ]);
            }
        }
    }
}
