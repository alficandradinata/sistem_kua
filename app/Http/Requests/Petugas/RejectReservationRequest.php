<?php

namespace App\Http\Requests\Petugas;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * [SISTEM KUA] Validasi penolakan reservasi oleh petugas.
 */
class RejectReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole([User::ROLE_PETUGAS, User::ROLE_ADMIN]) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => 'alasan penolakan'];
    }
}
