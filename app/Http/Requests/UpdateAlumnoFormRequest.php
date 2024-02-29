<?php

namespace App\Http\Requests;

use App\Http\Resources\AlumnoCollection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlumnoFormRequest extends FormRequest
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
        var_dump($this->alumno);
        return [
            "data.attributes.nombre"=>"required|min:5",
            "data.attributes.direccion"=>"required",
            "data.attributes.email"=>["required","email",
            Rule::unique("alumnos","email")->ignore($this->alumno)]
            //
        ];
    }
}
