<?php

namespace App\Http\Requests;

use App\Models\Rum;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class StoreRumPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $user = User::find(auth()->user()->id);
        $rum = $this->checkRum();
        return $this->storeRumTypeCase($user, $rum) && $this->storeRumPrivilegeCase($user, $rum);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'rum_id' => 'required|exists:App\Models\Rum,id',
            'title' => 'string|max:255',
            'description' => 'string',
            'metadata' => 'array',
            'image' => 'image|mimes:jpg,png,jpeg,gif,svg',
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

    private function checkRum()
    {
        $rum = Rum::find($this->request->all()['rum_id']);

        if (!$rum) {
            throw new HttpResponseException(
                response()->json(['error' => 'Not a valid rum.'], Response::HTTP_UNPROCESSABLE_ENTITY)
            );
        }

        return $rum;
    }

    private function storeRumTypeCase(User $user, Rum $rum)
    {
        switch ($rum->type) {
            case Rum::TYPE_PAID;
                return $rum->subscribed->contains(function($item) use($user) { return $item->id === $user->id; }) ||
                    $rum->admins->contains(function($item) use($user) { return $item->id === $user->id; }) ||
                    $rum->user_id === $user->id;
            default;
                return $rum->users->contains(function($item) use($user) { return $item->id === $user->id; }) ||
                    $rum->admins->contains(function($item) use($user) { return $item->id === $user->id; }) ||
                    $rum->user_id === $user->id;
        }
    }

    private function storeRumPrivilegeCase(User $user, Rum $rum)
    {
        switch ($rum->privilege) {
            case Rum::FOR_ALL;
                return true;
            case Rum::FOR_ME;
                return $rum->user_id === $user->id;
            case Rum::FOR_MEMBERS;
                return true;
            default;
                return false;
        }
    }
}
