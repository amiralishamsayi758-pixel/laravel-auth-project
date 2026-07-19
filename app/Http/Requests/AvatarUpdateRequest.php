<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class AvatarUpdateRequest extends FormRequest
{
    protected $errorBag = 'avatarUpdate';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2 * 1024),
            ],
        ];
    }
}
