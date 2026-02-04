<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('approval.reject');
    }

    public function rules(): array
    {
        return [
            'comments' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'comments.required' => 'Alasan penolakan harus diisi.',
            'comments.max' => 'Komentar maksimal 1000 karakter.',
        ];
    }
}
