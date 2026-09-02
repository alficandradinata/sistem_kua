<?php

namespace App\Http\Requests\Admin;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [SISTEM KUA] Validasi pembuatan laporan rekap.
 */
class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_ADMIN) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', Rule::in(array_keys(Report::TYPES))],
            'report_date' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'report_type' => 'jenis laporan',
            'report_date' => 'tanggal acuan',
        ];
    }
}
