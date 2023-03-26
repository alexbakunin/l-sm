<?php

namespace App\Http\Requests\Antiplagiat;

use Illuminate\Foundation\Http\FormRequest;

class CheckFileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file_id'   => ['required', 'integer'],
            'author_id' => ['required', 'integer'],
            'order_id'  => ['required', 'integer']
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
