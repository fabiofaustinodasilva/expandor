<?php

namespace App\Domains\Payments\Requests;

use App\Domains\Payments\Enums\BillingCycle;
use App\Domains\Payments\Enums\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'company_name' => ['required', 'string', 'max:190'],
            'buyer_name' => ['required', 'string', 'max:190'],
            'buyer_email' => ['required', 'email', 'max:190'],
            'buyer_document' => ['nullable', 'string', 'max:40'],
            'buyer_phone' => ['nullable', 'string', 'max:40'],
            'billing_cycle' => ['nullable', Rule::enum(BillingCycle::class)],
            'payment_method' => ['nullable', 'string', Rule::in([
                'UNDEFINED', 'PIX', 'BOLETO', 'CREDIT_CARD',
                PaymentMethodType::Pix->value,
                PaymentMethodType::Boleto->value,
                PaymentMethodType::Card->value,
            ])],
        ];
    }
}
