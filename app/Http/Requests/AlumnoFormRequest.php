<?php

namespace App\Http\Requests;

use App\Http\Resources\AlumnoCollection;
use Illuminate\Foundation\Http\FormRequest;

class AlumnoFormRequest extends FormRequest
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
            "data.attributes.nombre"=>"required|min:5",
            "data.attributes.direccion"=>"required",
            "data.attributes.email"=>"required|email|unique:alumnos,email"
            //
        ];
    }
}
