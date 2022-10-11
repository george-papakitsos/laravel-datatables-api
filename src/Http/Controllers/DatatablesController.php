<?php

namespace GPapakitsos\LaravelDatatables\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use GPapakitsos\LaravelDatatables\Datatables;

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
