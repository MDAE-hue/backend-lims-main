<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['middleware' => 'jwt'], function () use ($router) {
    $router->get('/dashboard', function () {
        return response()->json(['message' => 'Welcome to Dashboard, protected by JWT']);
    });
});

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('login', 'AuthController@login');

    $router->group(['middleware' => 'jwt'], function () use ($router) {

        $router->group(['middleware' => 'role:Admin'], function () use ($router) {
            $router->post('users', 'UserController@store');
            $router->put('users/{id}', 'UserController@update');
            $router->delete('users/{id}', 'UserController@destroy');

            // $router->delete('laboratory/reports/{id}', 'ReportController@destroy');
            $router->delete('laboratory/reports', 'ReportController@destroyMultiple');
        });

        $router->get('users', 'UserController@index');
        $router->get('users/{id}', 'UserController@show');
        $router->post('change-password', 'UserController@changePassword');

        $router->get('departments', 'DepartmentController@index');
        $router->get('departments/{id}', 'DepartmentController@show');
    $router->post('departments', 'DepartmentController@store');
    $router->put('departments/{id}', 'DepartmentController@update');
    $router->delete('departments/{id}', 'DepartmentController@destroy');
        $router->get('roles', 'RoleController@index');

        $router->get('laboratory/reports', 'ReportController@index');
        $router->get('/laboratory/reports/stats', 'ReportController@stats');
        $router->get('laboratory/reports/{id}', 'ReportController@show');
        $router->post('laboratory/reports', 'ReportController@store');
        $router->put('laboratory/reports/{id}', 'ReportController@update');

        $router->get('laboratory/reports/{id}/coa', 'ReportController@generateCoA');

        $router->get('laboratory/reports/{report_id}/test-details', 'TestController@getTestDetails');
        $router->get('laboratory/reports/{report_id}/test-details-view', 'TestController@getTestDetailsView');
        $router->post('test-details', 'TestController@storeTestDetail');
        $router->put('test-details/{id}', 'TestController@updateTestDetail');

        $router->get('test-types', 'TestController@getTestTypes');
        $router->get('methods', 'TestController@getMethods');
        $router->get('standards', 'TestController@getStandards');
        $router->get('units', 'TestController@getUnits');

    $router->get('test-types/{id}', 'TestTypeController@show');
    $router->post('test-types', 'TestTypeController@store');
    $router->put('test-types/{id}', 'TestTypeController@update');
    $router->delete('test-types/{id}', 'TestTypeController@destroy');

    $router->post('methods', 'MethodsController@store');
    $router->put('methods/{id}', 'MethodsController@update');
    $router->delete('methods/{id}', 'MethodsController@destroy');

    $router->post('standards', 'StandardsController@store');
    $router->put('standards/{id}', 'StandardsController@update');
    $router->delete('standards/{id}', 'StandardsController@destroy');

    $router->post('units', 'UnitsController@store');
    $router->put('units/{id}', 'UnitsController@update');
    $router->delete('units/{id}', 'UnitsController@destroy');

        $router->get('activity-logs', 'ActivityLogController@index');

        $router->group(['middleware' => 'role:Admin,Superintendent,Manager'], function () use ($router) {
            $router->get('laboratory/reports/{id}/take-action', 'ReportController@getTakeActionData');
            $router->put('laboratory/reports/{id}/take-action', 'ReportController@takeAction');
            $router->post('laboratory/reports/{id}/reject', 'ReportController@reject');
            $router->get('laboratory/reports/{id}/review', 'ReportController@review');
            $router->put('laboratory/reports/{id}/submit-review', 'ReportController@submitReview');
        });

        $router->group(['middleware' => 'role:Admin,Manager'], function () use ($router) {
            $router->put('laboratory/reports/{id}/approve', 'ReportController@approveReport');
        });

        $router->group(['middleware' => 'role:Admin,Superintendent,Analyst'], function () use ($router) {
            $router->put('laboratory/reports/{id}/finalize', 'ReportController@finalize');
        });

    });
});
