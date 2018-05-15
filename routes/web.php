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

Route::get('/', function () {
    return view('welcome');
});
Route::get('userss/index','UserssController@index');
Route::get('userss/index1','UserssController@index1');
Route::get('users/create', 'UserController@create')->name('users.create');
Route::post('users/store','UserController@store')->name('users.store');
Route::get('users/{user}','UserController@show')->name('users.show');
