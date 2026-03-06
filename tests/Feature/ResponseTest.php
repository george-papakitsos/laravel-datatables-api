<?php

namespace GPapakitsos\LaravelDatatables\Tests\Feature;

use GPapakitsos\LaravelDatatables\Tests\FeatureTestCase;
use GPapakitsos\LaravelDatatables\Tests\Models;
use Illuminate\Support\Arr;

class ResponseTest extends FeatureTestCase
{
    public function test_request_data_without_columns()
    {
        $request_without_columns = Arr::except($this->getRequestDataSample(), ['columns']);
        $response = $this->get('/'.$this->route_prefix.'/User?'.http_build_query($request_without_columns));

        $response->assertBadRequest();
    }

    public function test_request_data_without_length()
    {
        $request_without_length = Arr::except($this->getRequestDataSample(), ['length']);
        $response = $this->get('/'.$this->route_prefix.'/User?'.http_build_query($request_without_length));

        $response->assertBadRequest();
    }

    public function test_request_data_without_paging()
    {
        $request_without_paging = $this->getRequestDataSample();
        $request_without_paging['length'] = '-1';
        $response = $this->get('/'.$this->route_prefix.'/User?'.http_build_query($request_without_paging));

        $response->assertStatus(200);
        $response->assertJsonCount($response->offsetGet('recordsTotal'), 'data');
    }

    public function test_request_data_without_start()
    {
        $request_without_start = Arr::except($this->getRequestDataSample(), ['start']);
        $response = $this->get('/'.$this->route_prefix.'/User?'.http_build_query($request_without_start));

        $response->assertStatus(200);
    }

    public function test_request_data_without_order()
    {
        $request_without_order = Arr::except($this->getRequestDataSample(), ['order']);
        $response = $this->get('/'.$this->route_prefix.'/User?'.http_build_query($request_without_order));

        $response->assertStatus(200);
    }

    public function test_request_data_with_empty_order()
    {
        $request_with_empty_order = Arr::except($this->getRequestDataSample(), ['order.0']);
        $response = $this->get('/'.$this->route_prefix.'/User?'.http_build_query($request_with_empty_order));

        $response->assertStatus(200);
    }

    public function test_request_data_without_order_dir()
    {
        $request_without_order_dir = Arr::except($this->getRequestDataSample(), ['order.0.dir']);
        $response = $this->get('/'.$this->route_prefix.'/User?'.http_build_query($request_without_order_dir));

        $response->assertStatus(200);
    }

    public function test_model_in_another_namespace()
    {
        $request_data = $this->getRequestDataSample(Models\Locations\Country::class);
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/'.urlencode('Locations\Country').'?'.$query_string);
        $response->assertStatus(200);
        $response->assertJsonCount($request_data['length'], 'data');
    }

    public function test_model_without_get_datatables_data_method()
    {
        $request_data = $this->getRequestDataSample(Models\UserLogin::class);
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/UserLogin?'.$query_string);
        $response->assertBadRequest();
    }

    public function test_model_without_scope_search_method()
    {
        $request_data = $this->getRequestDataSample(Models\Locations\Country::class);
        $request_data['search']['value'] = 'term';
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/'.urlencode('Locations\Country').'?'.$query_string);
        $response->assertBadRequest();
    }

    public function test_response_length()
    {
        $request_data = $this->getRequestDataSample();
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $response->assertJsonCount($request_data['length'], 'data');
    }

    public function test_scope()
    {
        $request_data = $this->getRequestDataSample();
        $request_data['scope'] = 'test';
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_scope_array()
    {
        $request_data = $this->getRequestDataSample();
        $request_data['scope'] = ['byEmail', 'papakitsos_george@yahoo.gr'];
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_extra_where()
    {
        $request_data = $this->getRequestDataSample();
        $request_data['extraWhere']['id'] = 1;
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_extra_where_array()
    {
        $request_data = $this->getRequestDataSample();
        $request_data['extraWhere']['id'][] = 1;
        $request_data['extraWhere']['id'][] = 2;
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_sorting()
    {
        $request_data = $this->getRequestDataSample();
        $request_data['order'][0]['column'] = 3;
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $this->assertEquals($this->user->id, $response->getData(true)['data'][0]['id']);
    }

    public function test_sort_by_belongs_to_column()
    {
        $request_data = $this->getRequestDataSample();
        $request_data['order'][0]['column'] = 5;
        $request_data['order'][0]['dir'] = 'desc';
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $this->assertEquals($this->user->id, $response->getData(true)['data'][0]['id']);
    }

    public function test_sort_by_has_many_column()
    {
        $request_data = $this->getRequestDataSample();
        $request_data['order'][0]['column'] = 6;
        $request_data['order'][0]['dir'] = 'desc';
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $this->assertEquals($this->user->id, $response->getData(true)['data'][0]['id']);
    }

    public function test_sort_by_has_one_column()
    {
        $request_data = $this->getRequestDataSample();
        $request_data['order'][0]['column'] = 8;
        $request_data['order'][0]['dir'] = 'asc';
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $this->assertEquals(Models\User::orderBy('name')->orderBy('email')->first()->id, $response->getData(true)['data'][0]['id']);
    }

    public function test_search()
    {
        $request_data = $this->getRequestDataSample();
        $request_data['search']['value'] = 'Papakitsos';
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_search_by_column()
    {
        $request_data = $this->getRequestDataSample();
        $request_data['columns'][2]['search']['value'] = 'papakitsos_george@yahoo.gr';
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_search_by_column_date()
    {
        $request_data = $this->getRequestDataSample();
        $request_data['columns'][3]['search']['value'] = '23/04/1981'.config('datatables.filters.date_delimiter').'23/04/1981';
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_search_by_column_json()
    {
        $request_data = $this->getRequestDataSample();
        $request_data['columns'][7]['search']['value'] = 'PAPAKI';
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_search_by_belongs_to_column()
    {
        $request_data = $this->getRequestDataSample();
        $request_data['columns'][5]['search']['value'] = $this->country->name;
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $this->assertEquals($this->user->id, $response->getData(true)['data'][0]['id']);
    }

    public function test_search_by_belongs_to_date_column()
    {
        foreach (['15/06/1995', '15', '15/', '15/06', '15/06/'] as $searchValue) {
            $request_data = $this->getRequestDataSample();
            $request_data['columns'][5]['search']['value'] = $searchValue;
            $response = $this->get('/'.$this->route_prefix.'/User?'.http_build_query($request_data));

            $response->assertStatus(200);
            $response->assertJsonCount(1, 'data');
            $this->assertEquals($this->user->id, $response->getData(true)['data'][0]['id']);
        }
    }

    public function test_search_by_has_one_column()
    {
        foreach (['Papakitsos', 'papakitsos_george@yahoo.gr'] as $searchTerm) {
            $request_data = $this->getRequestDataSample();
            $request_data['columns'][8]['search']['value'] = $searchTerm;
            $query_string = http_build_query($request_data);

            $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
            $response->assertStatus(200);
            $this->assertEquals($this->user->id, $response->getData(true)['data'][0]['id']);
        }
    }

    public function test_search_by_column_null()
    {
        $request_data = $this->getRequestDataSample();
        $request_data['columns'][7]['search']['value'] = config('datatables.filters.null_delimiter');
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $this->assertEquals(Models\User::whereNull('settings')->count(), $response->getData(true)['recordsFiltered']);
    }

    public function test_search_by_relation_column_null()
    {
        $request_data = $this->getRequestDataSample();
        $request_data['columns'][5]['search']['value'] = config('datatables.filters.null_delimiter');
        $query_string = http_build_query($request_data);

        $response = $this->get('/'.$this->route_prefix.'/User?'.$query_string);
        $response->assertStatus(200);
        $this->assertEquals(Models\User::whereNull('country_id')->count(), $response->getData(true)['recordsFiltered']);
    }
}
