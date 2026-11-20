<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'address_id' => ['required', 'integer', Rule::exists('customer_addresses', 'id')],
            'courier_id' => ['required', 'integer', Rule::exists('couriers', 'id')->where('is_active', true)],
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_fee' => ['required', 'numeric', 'min:0'],
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')->where('is_active', true)],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'address_id' => 'alamat pengiriman',
            'courier_id' => 'kurir pengiriman',
            'shipping_name' => 'layanan pengiriman',
            'shipping_fee' => 'ongkos kirim',
            'payment_method_id' => 'metode pembayaran',
        ];
    }
}
