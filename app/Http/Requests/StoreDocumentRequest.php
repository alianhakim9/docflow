<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('document.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_type_id' => [
                'required',
                'integer',
                'exists:document_types,id'
            ],
            'title' => [
                'required',
                'string',
                'max:500'
            ],
            'data' => [
                'required',
                'array'
            ]
        ];
    }

    public function message(): array
    {
        return [
            'document_type_id.required' => 'Tipe dokumen harus dipilih.',
            'document_type_id.exists' => 'Tipe dokumen tidak ditemukan.',
            'title.required' => 'Judul harus diisi.',
            'title.max' => 'Judul maksimal 500 karakter.',
            'data.required' => 'Data form harus diisi.',
        ];
    }
}
