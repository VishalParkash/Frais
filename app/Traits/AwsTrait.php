<?php
namespace App\Traits;
use AWS;
use Illuminate\Support\Facades\Storage;
use App\Category;
use App\Country;
use App\Currency;

trait AwsTrait{
	function sendSms($mobile_number, $msg){
        $sms = AWS::createClient('sns');
        $result = $sms->publish([
        'Message' => $msg,
        'PhoneNumber' => $mobile_number,
        'MessageAttributes' => [
            'AWS.SNS.SMS.SMSType'  => [
                'DataType'    => 'String',
                'StringValue' => 'Transactional',
             ]
         ],
      ]);
        $meta = $result->get('@metadata');
        // echo $meta['statusCode'];die;
            if($meta['statusCode'] === 200){
              return true;
            }else{
                return false;
            }
    }

    // function uploadFileToS3($file, $FiltType){

    // }

    // public function uploadFile($getImageData, $FileType=false, $search_id=false){
    function uploadFileToS3($file, $FileType){
        $getImageData = $file;

        if (!empty($getImageData)) {

            if($FileType == 'profile'){
                $directory = 'profiles';
            }elseif($FileType == 'receipt'){
                $directory = 'expenses';
            }elseif($FileType == 'icon'){
                $directory = 'icons';
            }elseif($FileType == 'currency'){
                $directory = 'currencies';
            }elseif($FileType == 'category'){
                $directory = 'categories';
            }elseif($FileType == 'country'){
                $directory = 'countries';
            }
            else{
                $directory = 'profiles';
            }
            $response = array();
            $ImageArray = explode(";base64,", $getImageData);           //explode the image
            $forType = $ImageArray[0];                                  //get image type
            $forImage = $ImageArray[1];                                 //base64 encrypted image    
            $toGetType = explode("/", $forType);
            
            $extn_type = $toGetType[1];
            if($extn_type == 'svg+xml'){
                $extn_type = 'svg';
            }
             
            //decoding the base64 image to a normal image
            $image_base64 = base64_decode($forImage);

            //unique name for the image
            $fileSave = 'expenseRocket_'.date('His-mdY')."_".uniqid() .".".$extn_type;    

            //uploading the image to the s3 bucket
            try{
                if($FileType == 'receipt'){
                   (Storage::disk('local')->put($directory.'/' . $fileSave, $image_base64));
                }else{
                    (Storage::disk('s3')->put($directory.'/' . $fileSave, $image_base64));
                }
            	
            	$response['status'] = true;
            	$response['result'] = $fileSave;
            }catch(\Exception $Ex){
            	$response['status'] = false;
            	// $response['message'] = "File cannot be uploaded. Please try again or contact your administrator if the error persists.";	
                $response['message'] = $Ex->getMessage();    
            }

            
        }else{
            $response['status'] = false;
            $response['message'] = 'No file uploaded or invalid file type.';
        }
        return $response;
    }

    function getImageFromS3($search_id, $searchType){

        if($searchType == 'User'){
            $getFile = User::select('image')->where("id","=",$search_id)->first()->toArray();
            $File = $getFile['image'];
            $key = "profiles/".$File;
        }elseif ($searchType == 'Icons') {
            $getFile = Category::select('icon')->where("id","=",$search_id)->first()->toArray();
            $File = $getFile['icon'];
            $key = "icons/".$File;
        }elseif ($searchType == 'Expenses') {
            $getFile = Image::select('updated_name')->where("id", "=", $search_id)->first();
            
            $File = $getFile->updated_name;
            $key = "images/".$File;
        }elseif ($searchType == 'Thumbnail') {
            $getFile = Image::select('updated_name')->where("id", "=", $search_id)->first();
            
            $File = $getFile->updated_name;
            $key = "thumbnail/".$File;
        }elseif ($searchType == 'Currencies') {
            $getFile = Currency::select('CurrencyIcon')->where("id", "=", $search_id)->first();
            $File = $getFile->CurrencyIcon;
            $key = "currencies/".$File;
        }elseif ($searchType == 'Category') {
            $getFile = Category::select('icon')->where("id", $search_id)->first();
            $File = $getFile->icon;
            $key = "categories/".$File;
        }elseif ($searchType == 'Country') {
            $getFile = Country::select('flag')->where("id", $search_id)->first();
            $File = $getFile->flag;
            $key = "countries/".$File;
        }

        if(empty($File)){
            return null;
        }
        $BucketName = 'expmanager';

        $s3 = \Storage::disk('s3');
        if (!$s3->exists($key)) {
            if($searchType == 'User'){
                if(!empty(file_get_contents($File))){
                    return $File;
                }else{
                    return null;
                }
            }return null;
        }else{
            $client = $s3->getDriver()->getAdapter()->getClient();
            $command = $client->getCommand('GetObject', [
                'Bucket' => $BucketName,
                'Key'    => $key
            ]);

            $expiry = "+180 minutes";

            $request = $client->createPresignedRequest($command, $expiry);
            return $imageUrl =  (string) $request->getUri();
        }
    }

    function getFileFromS3WithName($FileName, $FileDirectory){
        $key = $FileDirectory."/".$FileName;
        if(empty($FileName)){
            return null;
        }
        $BucketName = 'expmanager';

        $s3 = \Storage::disk('s3');
        if (!$s3->exists($key)) {
            return null;
        }else{
            $client = $s3->getDriver()->getAdapter()->getClient();
            $command = $client->getCommand('GetObject', [
                'Bucket' => $BucketName,
                'Key'    => $key
            ]);

            $expiry = "+180 minutes";

            $request = $client->createPresignedRequest($command, $expiry);
            return $imageUrl =  (string) $request->getUri();
        }
    }
}