<?php

use Illuminate\Support\Facades\Route;

Route::get('/{model}', 'DatatablesController@getData')->name(config('datatables.routes.name'));
