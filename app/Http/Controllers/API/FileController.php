<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\AwsTrait;
use App\File;
use Illuminate\Support\Facades\Auth;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Textract\TextractClient;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
	use AwsTrait;
    public function index($FileType){
    	$requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

    	$User = Auth::user()->id;
    	// echo "<pre>";print_r($User);die;
        // echo "<pre>";print_r($userRequest);die;
    	$UploadingFile = $userRequest->file;
    	$ReturnValue = $this->uploadFileToS3($UploadingFile, $FileType);
    	// echo "<pre>";print_r($ReturnValue);die;
    	if($ReturnValue['status']){
    		$File = new File();
    		if(!is_null($File)){
    			$File->user_id = $User;
    			$File->FileType = $FileType;
    			$File->File = $ReturnValue['result'];
    			$File->UploadType = 'userDefined';
    			try{
    				$File->save();
    				$response['status'] = true;
		        	$response['result'] = $ReturnValue['result'];
		        	$response['message'] = "success";
    			}catch(\Exception $Ex){
    				$response['status'] = false;
        			$response['result'] = null;
		        	$response['message'] = "Cannot save the file. Something went wrong.";
    			}
    		}
    	}else{
    		$response['status'] = false;
    		$response['result'] = null;
		    $response['message'] = $ReturnValue['message'];
    	}
    	return $response;
    }

    public function getFile($filename){
        $client = new TextractClient([
                'region' => 'us-east-1',
                'version' => 'latest',
                // 'credentials' => false
                'credentials' => [
                    'key'    => 'AKIAJSBMITBGJ4CLKOBQ',
                    'secret' => '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'
                ]
            ]);

        $filename = $this->getFileFromS3WithName($filename, 'expenses');
        $filename = public_path('/image/gst_proforma.jpg');
        $file = fopen($filename, "rb");
        $contents = fread($file, filesize($filename));
        // $contents = fread($file, '10');
        fclose($file);
        $options = [
            'Document' => [
                'Bytes' => $contents
            ],
            'FeatureTypes' => ['FORMS'], // REQUIRED
        ];
        $result = $client->analyzeDocument($options);
        return $result;
    }

    public function get_kv_map($filename){
        $client = new TextractClient([
            'region' => 'us-west-2',
            'version' => 'latest',
            // 'credentials' => false
            'credentials' => [
                'key'    => 'AKIAJSBMITBGJ4CLKOBQ',
                'secret' => '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'
            ]
        ]);


        // $filename = public_path('/image/gst_proforma.jpg');
        // $fileSize =  Storage::disk('local')->size("expenses/".$filename);
        // $size = Storage::disk('s3')->size("expenses/".$filename);

        // echo "<pre>";print_r($contents);
        // $filename = public_path('/image/gst_proforma.jpg');
        // echo $filename = Storage::disk('local')->url("app/expenses/".$filename);
        $filename = Storage::disk('local')->path('expenses/'.$filename);

        // $filename = $this->getFileFromS3WithName($filename, 'expenses');
     
        $file = fopen($filename, "rb");
        // $contents = fread($file, ($size));
        $contents = fread($file, (filesize($filename)));
        fclose($file);
        $options = [
            'Document' => [
                'Bytes' => $contents
            ],
            'FeatureTypes' => ['FORMS'], // REQUIRED
        ];
        $result = $client->analyzeDocument($options);
        // If debugging:
        // echo "<pre>"; print_r($result);die;


        $blocks = $result['Blocks'];
        $text='';
        foreach($blocks as $block){
     $block_id = $block['Id'];
    $block_map[$block_id] = $block;

    // $result_map['block_map'] = $block;
    if($block['BlockType'] == "KEY_VALUE_SET"){
        if($block['EntityTypes'][0] == 'KEY'){
            $key_map[$block_id] = $block;
        }else{
            $value_map[$block_id] = $block;
        }

    }   
}

$response['key_map'] = $key_map;
$response['value_map'] = $value_map;
$response['block_map'] = $block_map;
$response['BlockFound'] = $blocks;
return $response;

    }


    public function get_kv_relationship($key_map, $value_map, $block_map){
    $kvs = array();

    foreach ($key_map as $block_id => $key_block) {
        $value_block = $this->find_value_block($key_block, $value_map);
        $key = $this->get_text($key_block, $block_map);
        $val = $this->get_text($value_block, $block_map);
        $kvs[$key] = $val;
                
            }

            return $kvs;

}

    public function find_value_block($key_block, $value_map){
        foreach($key_block['Relationships'] as $relationship){
                if($relationship['Type'] == 'VALUE'){
                    foreach($relationship['Ids'] as $value_id){
                        $value_block = $value_map[$value_id];
                        // $key = get_text($key_block, $block_map);
                        // $val = get_text($value_block, $block_map);
                    }

                    //foreach($value_block)
                    // for value_id in relationship['Ids']:
                }

            }
            return $value_block;
}


    public function get_text($result, $blocks_map){

        $text='';

            if(!empty($result['Relationships'])){
        foreach ($result['Relationships'] as $relationship) {
            if($relationship['Type'] == 'CHILD'){
                foreach($relationship['Ids'] as $child_id){
                    $word = $blocks_map[$child_id];
                    // echo "<pre>";print_r($word);
                    if($word['BlockType'] == 'WORD'){
                        $text .= $word['Text'].' ';
                    }
                }
            }
        }
    }

    return $text;


        /*    text = ''
    if 'Relationships' in result:
        for relationship in result['Relationships']:
            if relationship['Type'] == 'CHILD':
                for child_id in relationship['Ids']:
                    word = blocks_map[child_id]
                    if word['BlockType'] == 'WORD':
                        text += word['Text'] + ' '
                    if word['BlockType'] == 'SELECTION_ELEMENT':
                        if word['SelectionStatus'] == 'SELECTED':
                            text += 'X '    

                                
    return text*/
    }

    public function print_kvs($kvs){
        $response = array();
        foreach ($kvs as $key => $value) {
                $key = trim($key);
                $value = trim($value);
                $response[$key] = $value;

            // echo ($key ." : ".$value);
            // echo "<br>";
                }
        return $response;
    }

    public function readOCR(Request $request, $filename){
        $Result = ($this->get_kv_map($filename));
        // echo "<pre>";print_r($Result);
        $key_map = $Result['key_map'];
        $value_map = $Result['value_map'];
        $block_map = $Result['block_map'];
        $BlockFound = $Result['BlockFound'];

        $kvs = $this->get_kv_relationship($key_map, $value_map, $block_map);


        $keyValueSet = $this->print_kvs($kvs);
        // echo "<pre>";
        // print_r($key_map);
        // die;
        //structure
        $forName = array('name','merchant','customer','client');
        $forAmount = array('amount','amount paid', 'total amount', 'total amount paid', 'net payable amount', 'total', 'amt', 'amount due', 'invoice total');
        $forInvoice = array('invoice no.','no.', 'bill no.', 'bill', 'invoice', 'invoice number','order number', 'order no.', 'order');
        $forTax = array('tax','tax payable', 'taxable amount', 'tax applicable', 'gst', 'total tax amount');
        $forDate = array('date','dated', 'date of issuance', 'issued on', 'bill date', 'issue date');
        $forAddress = array('address','ship to', 'shipping address', 'location', 'place', 'bill to');
        // $RecordFound = $keyValueSet;

        foreach ($keyValueSet as $key => $value) {
            if(in_array(strtolower($key), $forAmount)){
                $RecordFound['total_amount'] = $value;
            }if(in_array(strtolower($key), $forInvoice)){
                $RecordFound['bill_number'] = $value;
            }if(in_array(strtolower($key), $forName)){
                $RecordFound['merchant_name'] = $value;
            }if(in_array(strtolower($key), $forDate)){
                $RecordFound['bill_date'] = $value;
            }if(in_array(strtolower($key), $forAddress)){
                $RecordFound['address'] = $value;
            }
            // $RecordFound[$key] = $value;

        }

        if(empty($RecordFound['total_amount'])){
                $RecordFound['total_amount'] = null;
        }if(empty($RecordFound['bill_number'])){
                $RecordFound['bill_number'] = null;
        }if(empty($RecordFound['merchant_name'])){
                $RecordFound['merchant_name'] = null;
        }if(empty($RecordFound['bill_date'])){
                $RecordFound['bill_date'] = null;
        }if(empty($RecordFound['address'])){
                $RecordFound['address'] = null;
        }
        // echo "<pre>";print_r($RecordFound);die;
        // $response['actualData'] = $BlockFound;
        if(!empty($RecordFound)){
            $response['status'] = true;
            $response['message'] = "valid data";
            $response['result'] = $RecordFound;
        }else{
            $response['status'] = false;
            $response['message'] = "No records found";
            $response['result'] = null;
        }
        

        return $response;
    }
}
