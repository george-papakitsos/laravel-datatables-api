<?php

use GPapakitsos\LaravelDatatables\Http\Controllers\DatatablesController;
use Illuminate\Support\Facades\Route;

Route::get('/{model}', [DatatablesController::class, 'getData'])->whereAlpha('model')->name(config('datatables.routes.name'));
