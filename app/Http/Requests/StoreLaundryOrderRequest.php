<?php

namespace App\Http\Requests;

use App\Models\Customer;
use App\Models\LaundryOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLaundryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', LaundryOrder::class);
    }

    public function rules(): array
    {
        return [
            'customer_id'   => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required_without:customer_id', 'nullable', 'string', 'max:120'],
            'customer_type' => ['required', Rule::in(array_keys(Customer::TYPES))],
            'room_no'       => [
                'nullable',
                'string',
                'max:20',
                Rule::requiredIf(fn () => $this->input('customer_type') === Customer::TYPE_TENANT),
            ],
            'contact_no'     => ['nullable', 'string', 'max:30'],
            'date_received'  => ['required', 'date'],
            'payment_method' => ['required', Rule::in([LaundryOrder::METHOD_NONE, LaundryOrder::METHOD_CASH, LaundryOrder::METHOD_ONLINE])],
            'payment_status' => ['required', Rule::in([LaundryOrder::STATUS_PAID, LaundryOrder::STATUS_NP])],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['required', 'exists:services,id'],
            'items.*.weight_kg'  => ['required', 'numeric', 'min:0.1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'             => 'Add at least one laundry service.',
            'room_no.required'           => 'Room number is required for tenants.',
            'items.*.weight_kg.required' => 'Enter the weight for each service.',
        ];
    }
}
