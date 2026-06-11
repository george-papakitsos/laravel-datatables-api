<?php

namespace GPapakitsos\LaravelDatatables\Http\Controllers;

use GPapakitsos\LaravelDatatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DatatablesController extends Controller
{
    public function getData(Request $request): JsonResponse
    {
        $Datatable = new Datatables($request, $request->model ?? '');

        return $Datatable->response();
    }
}
