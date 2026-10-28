<?php

declare(strict_types=1);

namespace App\Http\Requests\Family;

use App\Actions\Approvals\RespondToApproval;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RespondApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('respond', $this->route('approval'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([RespondToApproval::APPROVE, RespondToApproval::DENY, RespondToApproval::REQUEST_INFO])],
            'notes' => ['nullable', 'string', 'max:1000', 'required_if:decision,'.RespondToApproval::REQUEST_INFO],
        ];
    }
}
