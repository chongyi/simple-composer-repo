<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group([], function (\Illuminate\Routing\Router $router) {
    $router->get('packages.json', 'PackageManagerController@getPackagesJson');
    $router->get('p/{provider}${hash}.json', 'PackageManagerController@getPackageIncludes');
    $router->get('p/{vendor}/{package}${hash}.json', 'PackageManagerController@getPackage');
    $router->get('storage/{vendor}/{package}/{hash}.{type}',
        'PackageManagerController@getPackageDist');
    $router->post('local-sync/web-hook/gateway/{gateway}', 'LocalSyncGatewayController@gateway');

    $router->group(['prefix' => 'manager'], function (\Illuminate\Routing\Router $router) {
        $router->get('/', 'ManagerController@home');
    });
});