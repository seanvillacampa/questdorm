<?php

namespace App\Http\Requests;

use App\Models\LaundryOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('order'));
    }

    public function rules(): array
    {
        return [
            'payment_status' => ['required', Rule::in([LaundryOrder::STATUS_PAID, LaundryOrder::STATUS_NP])],
            'payment_method' => ['required', Rule::in([LaundryOrder::METHOD_NONE, LaundryOrder::METHOD_CASH, LaundryOrder::METHOD_ONLINE])],
        ];
    }
}
