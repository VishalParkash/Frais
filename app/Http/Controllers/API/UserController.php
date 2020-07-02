<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\OtpUser;
use App\UniqueUrl;
use App\Traits\UuidTrait;
use App\Traits\AwsTrait;
use Carbon\Carbon;
use Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\SocialMail;
use App\Mail\InviteMail;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use UuidTrait, AwsTrait;
    public function mobileSetup(){

        $auth = new User();
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $validator = Validator::make($userRequestValidate, [
                'mobileNumber' => 'required|string',
            ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);      
        }
        $mobileNumber = $userRequest->mobileNumber;
        $userDeviceId = (!empty($userRequest->userDeviceId)) ? ($userRequest->userDeviceId) : ('');


        if(!$auth->getUserByColumn('mobileNumber', $mobileNumber)){
            $User = new User();
            $User->mobileNumber = $mobileNumber;
            $User->userDeviceId = $userDeviceId;
            try{
                $User->save();
                $User_id = $User->id;
                $response['status'] = true;
            }
            catch(\Exception $Ex){
                $response['status'] = false;
                // $response['message'] = $Ex->getMessage();
                $response['message'] = "Cannot save user. Something went wrong.";
                return $response;
            }
        }else{
            $User = $auth->getUserByColumn('mobileNumber',$mobileNumber);
            $User_id = $User->id;
        }

        $OtpUser = new OtpUser;
        $otp = rand(1000,9999); 
        if(!is_null($OtpUser)) {
            $OtpUser->user_id = $User_id;
            $OtpUser->otp = $otp;
            $OtpUser->otp_expiry_at = Carbon::now()->addMinutes(10);
            try{
                $OtpUser->save();
                // $response['status'] = true;The Phone number that you entered is already registered
            }
            catch(\Exception $Ex){
                $response['status'] = false;
                $response['message'] = "Cannot create code for user. Something went wrong.";
                // $response['message'] = $Ex->getMessage();
                return $response;
            }

            $msg= 'Your code to enter the frais is '.$otp.". The code is valid for next 10 minutes.";

            try{
                $this->sendSms($mobileNumber, $msg);
                $response['status'] = true;
                $response['message'] = "We just sent a verification code to ".$mobileNumber;
            }catch(\Exception $Ex){
                $response['status'] = false;
                $response['message'] = $Ex->getMessage();
                // $response['message'] = "Something went wrong. Please try again.";
                return $response;
            }
            
        }
        
        return $response;    
    }

    public function accessAfterOtp(){
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        if(empty($userRequest->otp)){
            $response['status'] = false;
            $response['message'] = 'Please submit the code.';
            return $response;
        }

        $OtpDataArray = OtpUser::where('otp', '=', $userRequest->otp)
                                ->orderBy('id', 'DESC')
                                ->limit(1)
                                ->first();


        if(!empty($OtpDataArray)){
            $OtpData = $OtpDataArray->toArray();
        }

        if(empty($OtpData)){
            $response['status'] = false;
            // $response['message'] = 'The code you entered has invalid.';
            $response['message'] = nl2br('The code you entered is invalid. \nTry entering the code again');
            return $response;
        }
        
        $currentTime = Carbon::now();
        $OtpExpiry = $OtpData['otp_expiry_at'];
        // if($currentTime->diffInMinutes($OtpExpiry) > 10){
        if($currentTime > ($OtpExpiry)) {
            $response['status'] = false;
            $response['message'] = nl2br('The code you entered has expired. \nTry entering the code again');
            return $response;
        }
        $User = User::find($OtpData['user_id']);
        $User->user_verified_at = Carbon::now();
        // $msg = "Hello, Welcome to the Frais. Now you can manage your expenses. For more information you can contact us at our email hello@millipixels.com";

        try{    
                $User->save();
                $OtpDataArray->otp_expiry_at = Carbon::now();
                $OtpDataArray->save();
                // $this->sendSms($User->mobileNumber, $msg);
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
                
                return $response;
            }
            return $response;
    }

    public function emailSetup(){

        $auth = new User();
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $validator = Validator::make($userRequestValidate, [
            'email' => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ]);      
        }

        $email = $userRequest->email;

        if(!$auth->getUserByColumn('email', $email)){
            $User = new User();
            $User->email = $email;
            try{
                $User->save();
                $User_id = $User->id;
                $response['status'] = true;
            }
            catch(\Exception $Ex){
                $response['status'] = false;
                // $response['message'] = $Ex->getMessage();
                $response['message'] = "Cannot save user. Something went wrong.";
                return $response;
            }
        }else{
            $User = $auth->getUserByColumn('email',$email);
            $User_id = $User->id;
        }
        
        $urlSuffix = uniqid(sha1(time()));
        $User->urlSuffix = $urlSuffix;
        $UniqueUrl = new UniqueUrl();
        if(!is_null($UniqueUrl)){
            $UniqueUrl->user_id = $User_id;
            $UniqueUrl->urlSuffix = $urlSuffix;
            $UniqueUrl->urlExpiry = Carbon::now()->addMinutes(1440);

            try{
                $UniqueUrl->save();
                Mail::to($email)->send(new InviteMail($User));
                $response['status'] = true;
                $response['message'] = "An invitation link has been sent to your email ".$email;
                // return $response;
            }catch(\Exception $Ex){
                $response['status'] = false;
                $response['message'] = $Ex->getMessage();
                // $response['message'] = "Something went wrong. Please try again.";
                return $response;
            }
        }else{
            $response['status'] = false;
            $response['message'] = "Something went wrong. Please try again.";
        }
        
            return $response;
    }

    public function emailLogin(){
        $auth = new User();
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $validator = Validator::make($userRequestValidate, [
            'email' => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ]);      
        }

        $email = $userRequest->email;

        if(!$auth->getUserByColumn('email', $email)){
            $User = new User();
            $User->email = $email;
            // $User->userDeviceId = $userDeviceId;
            try{
                $User->save();
                $User_id = $User->id;
                $response['status'] = true;
            }
            catch(\Exception $Ex){
                $response['status'] = false;
                // $response['message'] = $Ex->getMessage();
                $response['message'] = "Cannot save user. Something went wrong.";
                return $response;
            }
        }else{
            $User = $auth->getUserByColumn('email',$email);
            $User_id = $User->id;
        }
        // print_r($User_id);die;
        try{
                Mail::to($email)->send(new InviteMail($User));
                $response['status'] = true;
                $response['message'] = "An invitation link has been sent to your email ".$email;
                // return $response;
            }catch(\Exception $Ex){
                $response['status'] = false;
                $response['message'] = $Ex->getMessage();
                // $response['message'] = "Something went wrong. Please try again.";
                return $response;
            }
            return $response;
    }

    public function socialSetup(Request $request){

        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));
        if(empty($userRequest)){
            $response['status'] = false;
            $response['message'] = "Please enter valid parameters.";
            return $response;
        }

        $validator = Validator::make($userRequestValidate, [
            'email' => 'required|string|email',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);      
        }
        $User = User::where(['email' => $userRequest->email])->first();
        if($User){
            // $user->tokens()->where('tokenable_id', $user->id)->delete();
            $User->name                 = (!empty($userRequest->name)) ? ($userRequest->name) : ('');
            $User->image                = (!empty($userRequest->image)) ? ($userRequest->image) : ('');
            $User->userDeviceId         = (!empty($userRequest->userDeviceId)) ? ($userRequest->userDeviceId) : ('');
            $User->providerId           = (!empty($userRequest->providerId)) ? ($userRequest->providerId) : ('');
            $User->providerName         = (!empty($userRequest->providerName)) ? ($userRequest->providerName) : ('');
            $User->socialLoginResponse  = (!empty($userRequest->socialLoginResponse)) ? (json_encode($userRequest->socialLoginResponse)) : ('');


            try{
                $User->save();

                if($User->userVerificationStatus == 1){
                    $UserInfo = $User;
                }else{
                    $UserInfo = array('userVerificationStatus' => $User->userVerificationStatus);
                }

                $response['status'] = true;
                $response['message'] = 'Login Successful.';
                $response['result'] = $UserInfo;
                $response['token'] = $User->createToken('PersonalAccessToken_Expense')->accessToken;
                // $response['user'] = $User;
            }catch (\Exception $Ex) {
                $response['status'] = false;
                // $response['message'] = 'Something went wrong. Please try again.';
                $response['message'] = $Ex->getMessage();
                return $response;
            }
                        
        }else{
            $user['email'] = $userRequest->email;
            try{
                // echo "<pre>";print_r($userRequest->image);die;
                $User = new User();
                // ($User = User::create([
                //         'name'          => (!empty($userRequest->name)) ? ($userRequest->name) : (''),
                //         'email'         => (!empty($userRequest->email)) ? ($userRequest->email) : (''),
                //         'image'         => (!empty($userRequest->image)) ? ($userRequest->image) : (''),
                //         'userDeviceId'  => (!empty($userRequest->userDeviceId)) ? ($userRequest->userDeviceId) : (''),
                //         'providerId'    => (!empty($userRequest->providerId)) ? ($userRequest->providerId) : (''),
                //         'providerName'      => (!empty($userRequest->providerName)) ? ($userRequest->providerName) : (''),
                //         'socialLoginResponse'      => (!empty($userRequest->socialLoginResponse)) ? ($userRequest->socialLoginResponse) : (''),
                //         // 'userVerificationStatus'      => 1,
                //         'user_verified_at' => Carbon::now()
                //     ]));

                $User->name                 = (!empty($userRequest->name)) ? ($userRequest->name) : ('');
                $User->email                = (!empty($userRequest->email)) ? ($userRequest->email) : ('');
                $User->image                = (!empty($userRequest->image)) ? ($userRequest->image) : ('');
                $User->userDeviceId         = (!empty($userRequest->userDeviceId)) ? ($userRequest->userDeviceId) : ('');
                $User->providerId           = (!empty($userRequest->providerId)) ? ($userRequest->providerId) : ('');
                $User->providerName         = (!empty($userRequest->providerName)) ? ($userRequest->providerName) : ('');
                $User->socialLoginResponse  = (!empty($userRequest->socialLoginResponse)) ? (json_encode($userRequest->socialLoginResponse)) : ('');
                $User->user_verified_at = Carbon::now();
                // 'userVerificationStatus'      => 1;

                try {
                    $User->save();

                    if($User->userVerificationStatus == 1){
                    $UserInfo = $User;
                }else{
                    $UserInfo = array('userVerificationStatus' => $User->userVerificationStatus);
                }


                    Mail::to($userRequest->email)->send(new SocialMail($User));
                    $response['status'] = true;
                    $response['message'] = 'success';
                    $response['result'] = $UserInfo;
                    $response['token'] = $User->createToken('PersonalAccessToken_Expense')->accessToken;
                    return $response;
                } catch (\Exception $Ex) {
                    $response['status'] = false;
                    $response['message'] = $Ex->getMessage();
                    return $response;
                }

            
            }catch(\Exception $Ex){
                $response['status'] = false;
                // $response['message'] = "Cannot save user. Something went wrong.";
                $response['message'] = $Ex->getMessage();
                return $response;
            }
        }
        return $response;

    }

    public function details(){
        $getUser = Auth::user()->id;

        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $User = User::find($getUser);

        $validator = Validator::make($userRequestValidate, [
            'mobileNumber' => 'string|unique:users',
            'email' => 'string|unique:users',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
                
            ]);      
        }

        if(!empty($User)){
            if(!empty($userRequest->email)){
                $User->email = $userRequest->email;
            }elseif(!empty($userRequest->mobileNumber)){
                $User->mobileNumber = $userRequest->mobileNumber;
            }

            $User->name = $userRequest->name;
            $User->defaultCurrency = $userRequest->defaultCurrency;
            $User->userVerificationStatus = 1;

            try{
                $User->save();
                $response['status'] = true;
                $response['message'] = 'Details added';
                $response['result'] = $User;
            }catch(\Exception $Ex){
                $response['status'] = false;
                $response['message'] = 'Something went wrong. Please try again.';
                // $response['message'] = $Ex->getMessage();
                $response['result'] = null;
            }
            return $response;
        }
    }
}