<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiResourceController extends Controller
{
    protected $model; // model class to use when extending controller
    protected $with; // relations to load when getting model

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        $response = [];

        if (!$this->with) {
            $response = $this->model()::all();
        } else {
            $response = $this->model()::with($this->with)->get();
        }

        return response()->json($response);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $response = null;

        try {
            $response = $this->model()::create($request->all());
            if ($response && $this->with) {
                $response = $response->load($this->with);
            }
        } catch (\Exception $e) {
            $response = $this->errorResponse($e);
        }

        return response()->json($response);
    }


    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id): \Illuminate\Http\JsonResponse
    {
        $model = $this->model($id);

        return response()->json($model);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $model = $this->model($id);
        $model->update($request->all());
        if ($this->with) {
            $model = $model->load($this->with);
        }

        return response()->json($model);
    }


    /**
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id): \Illuminate\Http\JsonResponse
    {
        try {
            $model = $this->model($id);
            $response = $model ? $model->delete() : true;
        } catch (\Exception $e) {
            $response = $this->errorResponse($e);
        }

        return response()->json($response);
    }


    /**
     * @param  string  $id
     * @return mixed
     */
    private function model(string $id = '')
    {
        $modelClass = $this->model;
        $error = null;

        // if no model specified, return an error Response
        if (empty($this->model)) {
            $error = $this->errorResponse(new \Exception("The model class is required"));
        } else {
            if (!class_exists($modelClass)) { // if model class doesn't exists
                $error = $this->errorResponse(new \Exception("Specified model class [{$modelClass}] doesn't exits"));
            }
        }

        // if exists error, stop the execution
        if ($error) {
            abort(400, $error['error']);
        }

        // if specified and ID for the model
        if ($id) {
            $modelClass = $modelClass::find($id);
            if (!$modelClass) {
                abort(404, "Model [$id] not found");
            }
        } else {
            $modelClass = new $modelClass();
        }

        return $modelClass;
    }


    private function errorResponse(\Exception $e)
    {
        Log::error($e);

        $debug = env('APP_DEBUG');
        $response = [
            'error' => $debug ? $e->getMessage() : 'Something went wrong. Please try again later.',
        ];

        if ($debug) {
            $response['exception'] = [
                'code' => $e->getCode(),
                'stringTrace' => $e->getTraceAsString(),
                'trace' => $e->getTrace(),
            ];
        }

        return $response;
    }
}
