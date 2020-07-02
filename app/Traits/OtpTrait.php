<?php 
namespace App\Traits;
use App\OtpUser;
use Carbon\Carbon;

trait OtpTrait
{
    protected static function createOTP($user_id){

    $OtpUser = new OtpUser;
    $otp = rand(1000,9999); 
    if(!is_null($OtpUser)) {
        $OtpUser->user_id = $user_id;
        $OtpUser->otp = $otp;
        // $OtpUser->last_login = Carbon::now();
        $OtpUser->otp_expiry_at = Carbon::now()->addMinutes(10);
        // $OtpUser->otp_authCode = Str::random(60);
        $OtpUser->save();

        $response['otp'] = $otp;
        // $response['otp_authCode'] = $OtpUser->otp_authCode;

        return $response;
    }
    }
}