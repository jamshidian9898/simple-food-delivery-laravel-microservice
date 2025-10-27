<?php

namespace App\Http\Requests\Basket;

use Illuminate\Foundation\Http\FormRequest;

class AddBasketItemRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|int|exists:products,id',
            'quantity' => 'nullable|int|min:1',
            'note' => 'nullable|string|max"255'
        ];
    }

    public function getProductId()
    {
        return $this->input('product_id');
    }

    public function getQuantity(int $def = 1)
    {
        return $this->input('quantity') ?? $def;
    }

    public function getNote(string $def = '')
    {
        return $this->input('note') ?? $def;
    }
}
