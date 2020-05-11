<?php

namespace GPapakitsos\LaravelDatatables\Tests\Feature;

use GPapakitsos\LaravelDatatables\Tests\FeatureTestCase;

class MiddlewareTest extends FeatureTestCase
{
	protected function getEnvironmentSetUp($app)
	{
		parent::getEnvironmentSetUp($app);

		$app->get('config')->set('datatables.middleware', [
			'GPapakitsos\LaravelDatatables\Tests\Http\Middleware\Unauthorized',
		]);
	}

	public function testMiddleware()
	{
		$request_data = $this->getRequestDataSample();
		$query_string = http_build_query($request_data);

		$response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
		$response->assertUnauthorized();
	}
}
