<?php

namespace GPapakitsos\LaravelDatatables\Tests\Feature;

use GPapakitsos\LaravelDatatables\Tests\FeatureTestCase;

class ResponseTest extends FeatureTestCase
{
	public function testResponseLength()
	{
		$request_data = $this->getRequestDataSample();
		$query_string = http_build_query($request_data);

		$response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
		$response->assertStatus(200);
		$response->assertJsonCount($request_data['length'], 'data');
	}

	public function testScope()
	{
		$request_data = $this->getRequestDataSample();
		$request_data['scope'] = 'test';
		$query_string = http_build_query($request_data);

		$response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
		$response->assertStatus(200);
		$response->assertJsonCount(1, 'data');
	}

	public function testScopeArray()
	{
		$request_data = $this->getRequestDataSample();
		$request_data['scope'] = ['byEmail', 'papakitsos_george@yahoo.gr'];
		$query_string = http_build_query($request_data);

		$response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
		$response->assertStatus(200);
		$response->assertJsonCount(1, 'data');
	}

	public function testExtraWhere()
	{
		$request_data = $this->getRequestDataSample();
		$request_data['extraWhere']['id'] = 1;
		$query_string = http_build_query($request_data);

		$response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
		$response->assertStatus(200);
		$response->assertJsonCount(1, 'data');
	}

	public function testExtraWhereArray()
	{
		$request_data = $this->getRequestDataSample();
		$request_data['extraWhere']['id'][] = 1;
		$request_data['extraWhere']['id'][] = 2;
		$query_string = http_build_query($request_data);

		$response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
		$response->assertStatus(200);
		$response->assertJsonCount(2, 'data');
	}

	public function testSorting()
	{
		$request_data = $this->getRequestDataSample();
		$request_data['order'][0]['column'] = 3;
		$query_string = http_build_query($request_data);

		$response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
		$response->assertStatus(200);
		$this->assertEquals(1, $response->getData(true)['data'][0]['id']);
	}

	public function testSearch()
	{
		$request_data = $this->getRequestDataSample();
		$request_data['search']['value'] = 'Papakitsos';
		$query_string = http_build_query($request_data);

		$response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
		$response->assertStatus(200);
		$response->assertJsonCount(1, 'data');
	}

	public function testSearchByColumn()
	{
		$request_data = $this->getRequestDataSample();
		$request_data['columns'][2]['search']['value'] = 'papakitsos_george@yahoo.gr';
		$query_string = http_build_query($request_data);

		$response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
		$response->assertStatus(200);
		$response->assertJsonCount(1, 'data');
	}

	public function testSearchByColumnDate()
	{
		$request_data = $this->getRequestDataSample();
		$request_data['columns'][3]['search']['value'] = '23/04/1981'.config('datatables.filters.date_delimiter').'23/04/1981';
		$query_string = http_build_query($request_data);

		$response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
		$response->assertStatus(200);
		$response->assertJsonCount(1, 'data');
	}
}
