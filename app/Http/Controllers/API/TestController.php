<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Zap;

class TestController extends Controller
{
    public function index(){
    	$requestData = trim(file_get_contents("php://input"));
        $userRequest = (json_decode($requestData));
        $User = Auth::user()->id;

        $postFields['amount'] = $userRequest->amount;
        $postFields['Date']  = Carbon::now();
        $postFields['User']  = $User;



		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://hooks.zapier.com/hooks/catch/7910180/o8emkbp");
		// SSL important
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$output = curl_exec($ch);
		curl_close($ch);

		return "added";
    }

    public function trello(){
    	$requestData = trim(file_get_contents("php://input"));
        $userRequest = (json_decode($requestData));
        $User = Auth::user()->id;

        $postFields['name'] = $userRequest->name;
        $postFields['email'] = $userRequest->email;
        $postFields['contact'] = $userRequest->contact;
        $postFields['message'] = $userRequest->message;
        $postFields['Date']  = Carbon::now();
        // $postFields['User']  = $User;



		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://hooks.zapier.com/hooks/catch/7910180/o8exjmn/");
		// SSL important
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$output = curl_exec($ch);
		curl_close($ch);

		return "added";
    }

    public function jira(){
    	$requestData = trim(file_get_contents("php://input"));
        $userRequest = (json_decode($requestData));
        $User = Auth::user()->id;

        $postFields['Summary'] = $userRequest->Summary;
        $postFields['Description'] = $userRequest->Description;
        $postFields['Reporter'] = $userRequest->Reporter;
        $postFields['Priority'] = $userRequest->Priority;
        $postFields['Assignee'] = $userRequest->Assignee;
        $postFields['Date']  = Carbon::now();
        // $postFields['User']  = $User;



		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://hooks.zapier.com/hooks/catch/7910180/o8c4zmc/");
		// SSL important
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$output = curl_exec($ch);
		curl_close($ch);

		return "added";
    }

    public function asana(){
        $requestData = trim(file_get_contents("php://input"));
        $userRequest = (json_decode($requestData));
        $User = Auth::user()->id;
        $UserName = Auth::user()->name;

        $postFields['Name'] = "Expense added by user ".$UserName." for the amount of ".$userRequest->amount;
        $postFields['Notes'] = "Expense added by user ".$UserName;
        $postFields['Date']  = Carbon::now();
        // $postFields['User']  = $User;



        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://hooks.zapier.com/hooks/catch/7910180/o8n9kat/");
        // SSL important
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $output = curl_exec($ch);
        curl_close($ch);

        return "added";
    }

    public function hubspot(){
    	$requestData = trim(file_get_contents("php://input"));
        $userRequest = (json_decode($requestData));
        $User = Auth::user()->id;

        $postFields['firstname'] = $userRequest->firstname;
        $postFields['lastname'] = $userRequest->lastname;
        $postFields['email'] = $userRequest->email;
        $postFields['phone'] = $userRequest->phone;
        $postFields['message'] = $userRequest->message;
        $postFields['Date']  = Carbon::now();
        // $postFields['User']  = $User;



		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://hooks.zapier.com/hooks/catch/7910180/o8eviyn/");
		// SSL important
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$output = curl_exec($ch);
		curl_close($ch);

		return "added";
    }

    public function gsheet(){
    	$requestData = trim(file_get_contents("php://input"));
        $userRequest = (json_decode($requestData));
        // echo "<pre>";print_r($userRequest);die;
        $Zap = new Zap();
        if(!is_null($Zap)){
        	// $Zap->zapContent = $userRequest->data;
            $Zap->zapContent = $requestData;
        	try{
        		$Zap->save();
        		$response['status'] = true;
        		$response['result'] = $userRequest;
        	}catch(\Exception $ex){
        		$response['status'] = false;
        		$response['result'] = null;
        		$response['message'] = $ex->getMessage();
        	}
        }
        return $response;
        // $User = Auth::user()->id;
        // print_r($userRequest);die;
        // $postFields['firstname'] = $userRequest->firstname;
        // $postFields['lastname'] = $userRequest->lastname;
        // $postFields['email'] = $userRequest->email;
        // $postFields['phone'] = $userRequest->phone;
        // $postFields['message'] = $userRequest->message;
        // $postFields['Date']  = Carbon::now();
        // $postFields['User']  = $User;



		// $ch = curl_init();
		// curl_setopt($ch, CURLOPT_URL, "https://hooks.zapier.com/hooks/catch/7910180/o8eviyn/");
		// // SSL important
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		// curl_setopt($ch, CURLOPT_POST, true);
		// curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		// $output = curl_exec($ch);
		// curl_close($ch);

		// return "added";
    }
}
