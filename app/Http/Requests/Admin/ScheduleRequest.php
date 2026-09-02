<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * [SISTEM KUA] Validasi jam operasional. Disimpan sekaligus 7 hari (bulk update)
 * karena barisnya tetap: satu baris per hari dalam seminggu.
 */
class ScheduleRequest extends FormRequest
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
            'days' => ['required', 'array', 'size:7'],
            'days.*.is_active' => ['boolean'],
            'days.*.open_time' => ['nullable', 'required_if_accepted:days.*.is_active', 'date_format:H:i'],
            'days.*.close_time' => ['nullable', 'required_if_accepted:days.*.is_active', 'date_format:H:i', 'after:days.*.open_time'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $days = collect($this->input('days', []))
            ->map(fn (array $d) => [...$d, 'is_active' => filter_var($d['is_active'] ?? false, FILTER_VALIDATE_BOOL)])
            ->all();

        $this->merge(['days' => $days]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'days.*.open_time.required_if_accepted' => 'Jam buka wajib diisi untuk hari yang aktif.',
            'days.*.close_time.required_if_accepted' => 'Jam tutup wajib diisi untuk hari yang aktif.',
            'days.*.close_time.after' => 'Jam tutup harus lebih akhir dari jam buka.',
        ];
    }
}
