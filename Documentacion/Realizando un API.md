# Realizando un API
La idea es hacer un api Rest full, en la que se atiendan solicitudes dependiendo del verbo:

- GET
- POST
- DELETE
- PUT (UPDATE)
- PATCH (PARCIAL UPDATE)

## Pasos previos a la realización del API
1. Creamos un **nuevo proyecto** en Laravel
```php
laravel new api_alumnos --git
```
2. Creamos el **modelo**. Para poblar la tabla del modelo (necesitamos hacer la **migración** y el **factory**)
```php
cd api_alumnos
php artisan make:model Alumno -mf --api
```
Nos habrá creado 4 Clases:
- AlumnoController
- Alumno (Modelo)
- AlumnoFactory
- xxx_create_alumnos_table

3. Creamos las clases para **gestionar lo que enviaremos a cliente:**

```php
php artisan make:request AlumnoFormRequest
php artisan make:resource AlumnoResource
php artisan make:resource AlumnoCollection --collection
```
4. En **/routes/api.php** (acordarse de use \App\Http\Controllers\AlumnoController::class):
```php
Route::Resource("alumnos", AlumnoController::class);
Route::apiResource('alumnos', \App\Http\Controllers\AlumnoController::class);
```
5. Para comprobar que se han creados las **rutas**:
```php
php artisan route:list --path='api/alumnos'
```
6. Creamos la **tabla** a partir de la **migración**:
> En database/factories/AlumnoFactory.php
```php
public function definition(): array
    {
        return [
            "nombre" => fake()->name(),
            "direccion" => fake()->address(),
            "email" => fake()->email()
        ];
    }
```
>En database/migrations/xxx_create_alumnos_table.php
```php 
public function up(): void
    {
        Schema::create('alumnos', function (Blueprint $table) {
            $table->id();
            $table->string("nombre");
            $table->string("direccion");
            $table->string("email");
            $table->timestamps();
        });
    }
```
>En database/seeders/DatabaseSeeder.php
```php
public function run(): void
    {
        Alumno::factory(10)->create();
    }
```
7. Creamos base de datos (.env):
   **Añadimos** al .env:
```php
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=23306
DB_DATABASE=instituto
DB_USERNAME=alumno
DB_PASSWORD=alumno
DB_PASSWORD_ROOT=root
DB_PORT_PHPMYADMIN=8080

LANG_FAKE ="es_ES"
```
8. Creamos el docker-compose.yaml
```php
#Nombre de la version
version: "3.8"
services:
  mysql:
    # image: mysql <- Esta es otra opcion si no hacemos el build
    image: mysql

    # Para no perder los datos cuando destryamos el contenedor, se guardara en ese derectorio
    volumes:
      - ./datos:/var/lib/mysql
    ports:
      - ${DB_PORT}:3306
    environment:
      - MYSQL_USER=${DB_USERNAME}
      - MYSQL_PASSWORD=${DB_PASSWORD}
      - MYSQL_DATABASE=${DB_DATABASE}
      - MYSQL_ROOT_PASSWORD=${DB_PASSWORD_ROOT}

  phpmyadmin:
    image: phpmyadmin
    container_name: phpmyadmin  #Si no te pone por defecto el nombre_directorio-nombre_servicio
    ports:
      - ${DB_PORT_PHPMYADMIN}:80
    depends_on:
      - mysql
    environment:
      PMA_ARBITRARY: 1 #Para permitir acceder a phpmyadmin desde otra maquina
      PMA_HOST: mysql
```
9. Creamos los dockers y migramos las tablas:
```php
docker compose up -d
//Si queremos borrar los dockers que ya teníamos: 
// docker stop $(docker ps -a -q)
// docker rm $(docker ps -a -q)

php artisan migrate:fresh --seed
```
10. Instalamos **Postman** para más adelante:
```php
sudo apt install snapd
sudo apt update
sudo snap install postman
```
## Realizando el API: Creando el JSON

11. Reescribimos **toArray** en **AlumnoResource**
> /app/Http/Resources/AlumnoResource.php
```php
public function toArray(Request $request): array
    {

        return [
            "id" => (string)$this->id,
            "type" => "Alumnos",
            "attributes" => [
                "nombre" => $this->nombre,
                "direccion" => $this->direccion,
                "email" => $this->email,
            ],
            'links' => [
                'self' => url('api/alumnos/' . $this->id)
            ]

        ];
    }
```
12. En **AlumnoController** modificamos el index para que llame a collection.
> /app/Http/Controllers/AlumnoController.php
```php
public function index()
    {
        $alumnos= Alumno::all();
        return new AlumnoCollection($alumnos);
    }
    
```
13. En **AlumnoCollection** añadimos el jsonapi a un nivel general y añadimos el método toArray:
> /app/Http/Resources/AlumnoCollection.php
```php
public function with(Request $request)
    {
        return [
            "jsonapi"=>[
                "version"=>"1.0"
            ]

        ];

    }


public function toArray(Request $request): array
{
 return ["data"=>$this->collection];
}
```

Añadimos el metodo **with** de la Clase **AlumnoResource** para que aparezca el **jsonapi** de manera individual

```php
public function with(Request $request)
    {
    return[
        "jsonapi" => [
            "version"=>"1.0"
        ]
    ];
}
```

## Realizando el API: Gestión de errores en el GET
14. **Errores** que vamos a valorar:

| Tipo | Verbo | Tipo de error |Número de error|
| ------ | ------ | ------ | ------ |
| Request | GET, POST, PATCH, DELETE|Accept: application/vnd.api +json|406 Not Acceptable
| Request | GET |Error interno, p.e. base de datos| 500 Server Error
|Request|GET, POST, PATCH, DELETE|No existe el recurso |404 Not Found|
|Request|POST, PATCH, PUT|Fallo a la hora de validar datos|422 Validation Error |

15. En la Clase **Handler**
    reescribmios el metodo **render**
>**app/Exceptions/Handler.php**
 ```php
 public function render($request, Throwable $exception)
{
    // Errores de base de datos)
    if ($exception instanceof QueryException) {
        return response()->json([
            'errors' => [ 
                [
                    'status' => '500',
                    'title' => 'Database Error',
                    'detail' => 'Error procesando la respuesta. Inténtelo más tarde.'
                ]
            ]
        ], 500);
    }
    // Delegar a la implementación predeterminada para otras excepciones no manejadas
    return parent::render($request, $exception);
}
```
Además, creamos el método **invalidJson** para controlar la validación:
```php
se Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

protected function invalidJson($request, ValidationException  $exception):JsonResponse
{
    return response()->json([
        'errors' => collect($exception->errors())->map(function ($message, $field) use
        ($exception) {
            return [
                'status' => '422',
                'title' => 'Validation Error',
                'details' => $message[0],
                'source' => [
                    'pointer' => '/data/attributes/' . $field
                ]
            ];
        })->values()
    ], $exception->status);
}


//Controlamos la llamada el método invalidJson:
 if ($exception instanceof ValidationException) {
        return $this->invalidJson($request, $exception);
    }
```

16. Creamos un **middleware**
```bash 
php artisan make:middleware AlumnoHandleMiddleware
```

17. En la Clase **AlumnoHandleMiddleware**
    reescribmios el metodo **handle**

>app/Http/Middleware/AlumnoHandleMiddleware.php

```php 
public function handle(Request $request, Closure $next): Response
{
    if ($request->header('accept') != 'application/vnd.api+json') {
        return response()->json([
            "errors"=>[
                "status"=>406,
                "title"=>"Not Accetable",
                "deatails"=>"Content File not specifed"
            ]
        ],406);
    }
    return $next($request);
}
```
Para probarlo, en postman, quitamos el **Header Accept** y creamos uno nuevo con el siguiente valor:
```
application/vnd.api+json
```


18. Finalmente, asociamos el middleware a la ruta, a través de **kernel.php**

>app/Http/Kernel.php

```php
'api'=>[
    \App\Http\Middleware\AlumnoHandleMiddleware::class
    ...
]
```
## Realizando el API: Los Métodos (show, store, destroy, update)
#### Primero nos vamos a **Alumno.php** y rellenamos el $fillable
> app/http/Models/Alumno.php
```php
protected $fillable = ["nombre","direccion","email"];
```

### 19.  Método **store**
> app/Http/Controllers/AlumnoController.php
```php
public function store(AlumnoFormRequest $request)
{
    $datos = $request->input("data.attributes");
    $nombre = new Alumno($datos);
    $nombre->save();

    return new AlumnoResource($nombre);
}
```
20. Editamos la Clase **AlumnoFormRequest** para hacer la validación de datos:
> app/Http/Requests/AlumnoFormReques.php

```php
public function authorize(): bool
{
    return true;
}

public function rules(): array
{
    return [
        "data.attributes.nombre"=>"required|min:5",
        "data.attributes.direccion"=>"required",
        "data.attributes.email"=>"required|email|unique:alumnos,email"
    ];
}
```
### 21. Reescribimos el método **show**:
> app/Http/Controllers/AlumnoController.php

De esta forma, si un recurso no se encuentra se devolverá un **404** (Not Found).
```php
public function show(int $id){
 $resource = Alumno::find($id);
    if (!$resource) {
        return response()->json([
            'errors' => [
                [
                'status' => '404',
                'title' => 'Resource Not Found',
                'detail' => 'The requested resource does not exist or could not be found.'
                ]
            ]
        ], 404);
    }
 return new AlumnoResource($resource);
```
### 22. El método **destroy**:
Para eliminar, basta con seleccionar en POSTMAN el verbo **DELETE** y enviar. Se borrará la id seleccionada.
> app/http/Controllers/AlumnoController.php

```php
public function destroy(int $id)
{
    $nombre = Alumno::find($id);
    if (!$nombre) {
        return response()->json([
                'errors' => [
                    [
                    'status' => '404',
                    'title' => 'Resource Not Found',
                    'detail' => 'The requested resource does not exist or could not be found.'
                    ]
                ]
        ], 404);
    }

    $nombre->delete();
    return response()->json(null,204);
    //Devuelve un 204 de No Content 
}
```

### 23. El método **update**:
Antes de empezar, hay que recordar las diferencias entre **PATCH** y **PUT**.
- PUT (Neccesitamos **todos** los datos)
- PATCH (**Solo** introduccimos los datos a modificar)

> app/http/Controllers/AlumnoController.php
```php
public function update(Request $request, int $id)
{
    $nombre = Alumno::find($id);
    
    if (!$nombre) {
        return response()->json([
            'errors' => [
                [
                'status' => '404',
                'title' => 'Resource Not Found',
                'detail' => 'The requested resource does not exist or could not be found.'
                ]
            ]
        ], 404);
    }

    $verbo = $request->method();

    if ($verbo == "PUT") { //Valido por PUT
        $rules = [
            "data.attributes.nombre" => ["required", "min:5"],
            "data.attributes.direccion" => "required",
            "data.attributes.email" => ["required", "email", Rule::unique("alumnos", "email")->ignore($nombre)]
        ];

    } else { //Valido por PATCH
        if ($request->has("data.attributes.nombre"))
            $rules["data.attributes.nombre"]= ["required", "min:5"];
        if ($request->has("data.attributes.direccion"))
            $rules["data.attributes.direccion"]= ["required"];
        if ($request->has("data.attributes.email"))
            $rules["data.attributes.email"]= ["required", "email", Rule::unique("alumnos", "email")->ignore($nombre)];
    }

    $datos_validados = $request->validate($rules);

    foreach ($datos_validados['data']['attributes'] as $campo=>$valor)
        $datos[$campo] = $valor;

    $nombre->update($request->input("data.attributes"));

    return new AlumnoResource($nombre);
}
```
