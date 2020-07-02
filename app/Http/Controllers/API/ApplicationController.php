<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use App\User;
use App\UniqueUrl;
use Carbon\Carbon;

class ApplicationController extends Controller
{
    public function loginApp($urlSuffix){
    	$requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        if(empty($urlSuffix)){
            $response['status'] = false;
            $response['message'] = "The Url you have provided is invalid.";
            return $response;
        }
        $UniqueUrl = UniqueUrl::where('urlSuffix', $urlSuffix)->orderBy('id', 'DESC')->limit(1)->first();
        if(!empty($UniqueUrl)){
        	// echo $UniqueUrl->urlExpiry;
        	// echo "<br>";
        	// echo Carbon::now();
        	// die;
        	$currentTime = Carbon::now();
        	if($currentTime->gt($UniqueUrl->urlExpiry)){
    			$response['status'] = false;
	            $response['message'] = 'The Url you have provided is expired.';
	            return $response;
			}
        	elseif($currentTime->diffInMinutes($UniqueUrl->urlExpiry) > 1440){
        		 $response['status'] = false;
	            $response['message'] = 'The Url you have provided is expired.';
	            return $response;
        	}
        	$User = User::find($UniqueUrl->user_id);
        	if(!empty($User)){
        		$User->user_verified_at = Carbon::now();
        		// $User->userVerificationStatus = 1;

        		try{	
        		$User->save();
        		$UniqueUrl->urlExpiry = Carbon::now();
        		$UniqueUrl->save();

        		// $agent = new Agent();
        		// $device = $agent->device();

        		// if($device == 'iPhone'){
        		// 	$Url ='fraisexpensepro://magiclink?'.$urlSuffix;
          //           // $Url ='expmanager://magiclink?'.$urlSuffix;
        		// 	// $Url ='itms-apps:// ';
        		// }elseif($device == 'Android'){
        		// 	// $Url ='expmanager://magiclink?'.$urlSuffix;
          //           $Url ='fraisexpensepro://magiclink?'.$urlSuffix;
          //           // $Url ='https://play.google.com/';
        		// }else{
        		// 	$Url = url('/api/app/'.$urlSuffix);
        		// }
		        // $urlSuffix= uniqid(sha1(time()));
		        // echo url('/api/app/'.$urlSuffix);die;
        		// header("Location:".$Url);
        		// exit;
                if($User->userVerificationStatus == 1){
                    $UserInfo = $User;
                }else{
                    $UserInfo = array('userVerificationStatus' => $User->userVerificationStatus);
                }
        		$response['status'] = true;
        		$response['message'] = "success";
        		$response['result'] = $UserInfo;
        		$response['token'] = $User->createToken('PersonalAccessToken_Expense')->accessToken;
        	}catch(\Exception $Ex){
        		$response['status'] = false;
        		// $response['message'] = $Ex->getMessage();
        		$response['message'] = "Something went wrong. Please try again.";
        	}
        	
        	}
        	
        }else{
        		$response['status'] = false;
        		// $response['message'] = $Ex->getMessage();
        		$response['message'] = "The Url you have provided is invalid";
        }
        return $response;
    }

    public function magiclink($urlSuffix){
        $agent = new Agent();
                $device = $agent->device();

                if($device == 'iPhone'){
                    $Url ='fraisexpensepro://magiclink/'.$urlSuffix;
                    // $Url ='expmanager://magiclink?'.$urlSuffix;
                    // $Url ='itms-apps:// ';
                }elseif($device == 'Android'){
                    // $Url ='expmanager://magiclink?'.$urlSuffix;
                    $Url ='fraisexpensepro://magiclink/'.$urlSuffix;
                    // $Url ='https://play.google.com/';
                }else{
                    $Url = url('/api/app/'.$urlSuffix);
                }

                header("Location:".$Url);
                exit;
    }
}
