<?php

return [

	'models_namespace' => 'App\\Models\\',

	'routes' => [
		'prefix' => 'datatable',
		'name' => 'datatable',
	],

	'middleware' => [
		'web',
		// 'auth',
	],

	'filters' => [
		'date_format' => 'd/m/Y',
	],

];
