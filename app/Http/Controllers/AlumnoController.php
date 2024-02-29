<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlumnoFormRequest;
use App\Http\Requests\UpdateAlumnoFormRequest;
use App\Http\Resources\AlumnoCollection;
use App\Http\Resources\AlumnoResource;
use App\Models\Alumno;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlumnoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $alumnos= Alumno::all();
//        return response()->json($alumnos);
        return new AlumnoCollection($alumnos);
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AlumnoFormRequest $request)
    {

        $datos = $request->input("data.attributes");
        $alumno = new Alumno($datos);
        $alumno->save();
        return new AlumnoResource($alumno);
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $alumno = Alumno::find($id);
        if (!$alumno){
            return response()->json([
                "errors"=>[
                    'status' => '404',
                    'title' => 'Source not found',
                    'details' => 'Puede que se haya quitado el recurso que está buscando, que se le haya cambiado el nombre o que no esté disponible temporalmente.'
                ]
            ],404);
        }
        return new AlumnoResource($alumno);
//        return response()->json($alumno);

        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $alumno = Alumno::find($id);
        if (!$alumno) {
            return response()->json([
                "errors" => [
                    'status' => '404',
                    'title' => 'Source not found',
                    'details' => 'Puede que se haya quitado el recurso que está buscando, que se le haya cambiado el nombre o que no esté disponible temporalmente.'
                ]
            ], 404);

        }
            $verbo = $request->method();
            if ($verbo == "PUT") {//Valido PUT
                $rules = ["data.attributes.nombre" => "required|min:5",
                    "data.attributes.direccion" => "required",
                    "data.attributes.email" => ["required", "email",
                        Rule::unique("alumnos", "email")->ignore($alumno)]];
            } else {//Valido PATCH
                if ($request->has("data.attributes.nombre"))
                    $rules["data.attributes.nombre"] = ["required", "min:5"];
                if ($request->has("data.attributes.direccion"))
                    $rules["data.attributes.direccion"] = ["required"];
                if ($request->has("data.attributes.email"))
                    $rules["data.attributes.email"] = ["required", "email",
                        Rule::unique("alumnos", "email")
                            ->ignore($alumno)];
            }
            $datos_validados = $request->validate($rules);
            foreach ($datos_validados['data']['attributes'] as $campo => $valor)
                $datos[$campo] = $valor;

            $alumno->update($datos);
            return new AlumnoResource($alumno);
        }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $alumno = Alumno::find($id);
        if (!$alumno){
            return response()->json([
                "errors"=>[
                    'status' => '404',
                    'title' => 'Source not found',
                    'details' => 'Puede que se haya quitado el recurso que está buscando, que se le haya cambiado el nombre o que no esté disponible temporalmente.'
                ]
            ],404);
        }

         $alumno->delete();
         return response()->json(null,204);

        //
    }
}
