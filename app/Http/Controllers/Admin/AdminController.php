<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;

class AdminController extends Controller
{
    public function login(){
    	$requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $adminEmail = $userRequest->email;
        $User = User::where('email', $adminEmail)
        			->first();
		try{
			if(!empty($User)){
				$response['status'] = true;
				$response['message'] = "success";
				$response['token'] = $User->createToken('PersonalAccessToken_Expense')->accessToken;
			}
        }catch(\Exception $ex){
        	$response['status'] = false;
        	$response['message'] = $Ex->getMessage();
        }
       	return $response;
    }
}
