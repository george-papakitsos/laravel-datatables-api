<?php

namespace GPapakitsos\LaravelDatatables\Http\Controllers;

use GPapakitsos\LaravelDatatables\Datatables;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DatatablesController extends Controller
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getData($model)
    {
        $Datatable = new Datatables($this->request, $model);

        return $Datatable->response();
    }
}
