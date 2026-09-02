<?php

namespace App\Http\Requests\Admin;

use App\Models\AutoReply;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [SISTEM KUA] Validasi balasan otomatis WhatsApp.
 */
class AutoReplyRequest extends FormRequest
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
        $current = $this->route('auto_reply');

        return [
            'keyword' => [
                'required', 'string', 'max:100',
                Rule::unique('auto_replies', 'keyword')->ignore($current),
            ],
            'match_type' => ['required', Rule::in(array_keys(AutoReply::MATCH_TYPES))],
            'reply_body' => ['required', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order') ?: 0,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'keyword' => 'kata kunci',
            'match_type' => 'jenis pencocokan',
            'reply_body' => 'isi balasan',
            'sort_order' => 'urutan',
        ];
    }
}
