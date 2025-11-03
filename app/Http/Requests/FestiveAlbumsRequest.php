<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FestiveAlbumsRequest extends FormRequest
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
            'favourite_set'   => 'required|string|max:255',
            'favourite_day'   => 'required|string|max:255',
            'festive_date'    => 'required|date',
            'camp_experience' => 'required|string|max:255',
            'unique_moments'  => 'required|string',
            'dairy_entry'     => 'required|string',
            'status'          => 'required|in:public,private',
            'fest_type'       => 'required|in:upcoming,past',

            // ✅ Festive Album Images (Multiple Uploads)
            'images'                   => 'nullable|array',
            'images.*'                 => 'nullable|file|mimes:jpeg,jpg,png,webp,mp4,mov,avi|max:20480',

            // ✅ If frontend sends festive_album_id on update
            'festive_album_id' => 'sometimes|exists:festive_albums,id',
        ];
    }
}
