<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReturnDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('approval.return');
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
            'comments.required' => 'Alasan pengembalian harus diisi.',
            'comments.max' => 'Komentar maksimal 1000 karakter.',
        ];
    }
}
