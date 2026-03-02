<?php

namespace GPapakitsos\LaravelDatatables\Http\Controllers;

use GPapakitsos\LaravelDatatables\Datatables;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DatatablesController extends Controller
{
    public function __construct(protected Request $request) {}

    public function getData(): \Illuminate\Http\JsonResponse
    {
        $Datatable = new Datatables($this->request, $this->request->model ?? '');

        return $Datatable->response();
    }
}
