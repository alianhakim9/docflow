<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DelegateApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('approval.delegate');
    }

    public function rules(): array
    {
        return [
            'delegate_to' => ['required', 'integer', 'exists:users,id'],
            'end_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'delegate_to.required' => 'Pengguna penerima delegasi harus dipilih.',
            'delegate_to.exists' => 'Pengguna tidak ditemukan.',
            'end_date.after_or_equal' => 'Tanggal akhir harus hari ini atau setelahnya.',
        ];
    }
}
