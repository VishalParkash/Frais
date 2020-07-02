<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::post('/login', 'API\UserController@loginBeforeOtp');
Route::post('/mobile', 'API\UserController@mobileSetup');
Route::post('/access', 'API\UserController@accessAfterOtp');
Route::post('/email', 'API\UserController@emailSetup');
Route::post('/social', 'API\UserController@socialSetup');
Route::get('/magiclink/{token}', 'API\ApplicationController@magiclink')->name('magiclink');
Route::get('/access/{token}', 'API\ApplicationController@loginApp');
Route::get('/Usertype/', 'API\UserController@Usertype');
Route::get('/countries', 'API\CountryController@countries');
Route::get('/data', 'API\TestController@gsheet');
//Text Analysers API
Route::post('/IbmWatson', 'API\AnalyserController@IbmWatson');
Route::post('/MonkeyLearn','API\AnalyserController@MonkeyLearn');
Route::post('/GoogleNl','API\AnalyserController@GoogleNl');
Route::Post('/GoogleNlWatson','API\AnalyserController@GoogleWatson');


Route::group([
      'middleware' => 'auth:api'
    ], function() {
      
  Route::post('/details', 'API\UserController@details');
  Route::post('/file/{FileType}/', 'API\FileController@index');
  Route::post('/category', 'API\CategoryController@Category');
  Route::post('/category/{category}/', 'API\CategoryController@Category');
  Route::get('/categories', 'API\CategoryController@categories');
  
  Route::get('/currencies', 'API\CountryController@currencies');
  Route::post('/message', 'API\ChatController@message');
  Route::get('/messages/{receiver_id}/', 'API\ChatController@messages');
  // Route::get('/chat', 'API\ChatController@chat');
  Route::get('users', 'API\ChatController@UserList');
  Route::get('read/file/{file}/', 'API\FileController@getFile');
  Route::get('read/file/{file}/', 'API\FileController@readOCR');
  Route::post('/test', 'API\TestController@index');
  Route::post('/trello', 'API\TestController@trello');
  Route::post('/hubspot', 'API\TestController@hubspot');
  Route::post('/jira', 'API\TestController@jira');
  Route::post('/asana', 'API\TestController@asana');

  // Route::post('sendMessage', 'API\ChatController@sendMessage');
  // Route::post('getMessage', 'API\ChatController@getMessage');
    });

Route::prefix('/admin')->name('admin.')->group(function(){
	Route::post('login', 'Admin\AdminController@login')->name('login');
	

	// Route::group([
 //      'middleware' => 'auth:api'
 //    ], function() {
 //    	//Chat
 //    	//Profile Resources and Portfolios
        
 //    });
});
