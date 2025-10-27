<?php

namespace App\Http\Requests\Restaurant;

use Illuminate\Foundation\Http\FormRequest;

class RestaurantListRequest extends FormRequest
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
            'page' => 'int|min:1',
            'limit' => 'int|min:1'
        ];
    }

    public function getPage($def = null)
    {
        return $this->input('page') ?? $def;
    }

    public function getLimit($def = null)
    {
        return $this->input('limit') ?? $def;
    }
}
