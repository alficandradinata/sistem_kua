<?php

namespace App\Http\Requests\Admin;

use App\Models\Holiday;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * [SISTEM KUA] Validasi hari libur.
 */
class HolidayRequest extends FormRequest
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
            // Dicek dengan whereDate, bukan Rule::unique, karena kolomnya di-cast `date`
            // sehingga tersimpan sebagai "Y-m-d H:i:s" — perbandingan persis tidak cocok.
            'holiday_date' => ['required', 'date', function (string $attribute, mixed $value, Closure $fail) {
                $query = Holiday::whereDate('holiday_date', $value);

                if ($current = $this->route('holiday')) {
                    $query->whereKeyNot($current->getKey());
                }

                if ($query->exists()) {
                    $fail('Tanggal tersebut sudah terdaftar sebagai hari libur.');
                }
            }],
            'description' => ['required', 'string', 'max:150'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'holiday_date' => 'tanggal libur',
            'description' => 'keterangan',
        ];
    }
}
