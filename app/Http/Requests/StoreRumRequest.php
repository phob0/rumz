<?php

namespace App\Http\Requests;

use App\Models\Rum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class StoreRumRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'title' => 'required|max:255|string|unique:rums',
            'description' => 'string',
            'website' => 'string',
            'type' => [
                'required',
                Rule::in([
                    Rum::TYPE_FREE,
                    Rum::TYPE_PAID,
                    Rum::TYPE_CONFIDENTIAL,
                    Rum::TYPE_PRIVATE,
                ]),
            ],
            'privilege' => [
                'required',
                Rule::in([
                    Rum::FOR_ME,
                    Rum::FOR_MEMBERS,
                    Rum::FOR_ALL,
                ]),
            ],
            'image' => 'image|mimes:jpg,png,jpeg,gif,svg',
            'hashtags' => [
                'array'
            ]
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException;
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
