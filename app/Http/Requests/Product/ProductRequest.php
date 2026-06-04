<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $translation = DB::table('product_translations')->where('product_id', '=', $this->id)->where('locale', '=', app()->getLocale())->first();

        return [
            'user_id' => 'filled',
            'name' => 'required|min:5',
            'slug' => 'required|alpha_dash|unique:product_translations,slug,' . $translation?->id,
            'datetime' => 'required',
            'excerpt' => 'required|min:10',
            'purchased_price' => 'required',
            'price' => 'required',
        ];
    }
}
