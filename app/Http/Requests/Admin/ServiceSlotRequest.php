<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [SISTEM KUA] Validasi slot antrean. Kombinasi layanan + jam mulai wajib unik
 * karena perhitungan kuota memakai jam mulai sebagai kunci.
 */
class ServiceSlotRequest extends FormRequest
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
            'service_id' => ['required', Rule::exists('services', 'id')],
            'slot_start_time' => [
                'required', 'date_format:H:i:s',
                Rule::unique('service_slots', 'slot_start_time')
                    ->where(fn ($q) => $q->where('service_id', $this->input('service_id')))
                    ->ignore($this->route('slot')),
            ],
            'slot_duration' => ['required', 'integer', 'min:5', 'max:240'],
            'quota_per_day' => ['required', 'integer', 'min:0', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * `<input type="time">` mengirim H:i sementara kolomnya H:i:s. Samakan SEBELUM
     * divalidasi, kalau tidak aturan unique membandingkan "08:00" dengan "08:00:00"
     * dan duplikat lolos sampai ke constraint database.
     */
    protected function prepareForValidation(): void
    {
        $time = $this->input('slot_start_time');

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'slot_start_time' => is_string($time) && preg_match('/^\d{2}:\d{2}$/', $time) ? $time.':00' : $time,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'service_id' => 'layanan',
            'slot_start_time' => 'jam mulai',
            'slot_duration' => 'durasi slot',
            'quota_per_day' => 'kuota per hari',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slot_start_time.unique' => 'Layanan ini sudah punya slot pada jam tersebut.',
        ];
    }
}
