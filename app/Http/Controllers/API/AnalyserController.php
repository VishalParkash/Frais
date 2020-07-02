<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Google\Cloud\Core\ServiceBuilder;

class AnalyserController extends Controller
{
    public function IbmWatson(Request $request){
        $validator = \Validator::make($request->all(), [
            'message' => 'required',]);    

        if ($validator->fails()) {
            return response()->json([
                'status' => true,
                'message' => $validator->errors()->first()
            ],400);
        }else{
            $text = $request->message;
            // $text = 'I have purchased $799 petrol today from indian oil petrol pump delhi';
            // $text = 'I have spent $10000 on car purchase of Honda from noida';
            $url  = Config::get('constants.Analysers.IBMWatsonURL');
            $header_meta = array(
                "apikey: ".Config::get('constants.Analysers.IBMWatsonApiKEY'),
                "Authorization: Basic YXBpa2V5Ol9FQTNwWEVTVF9ESDNEVEJ6UHhIeUR6SnBXRDAxN2kxZ2xELWxqemVnLWdS",
                "Content-Type: application/json"
            );
            $post_data = "{\n    \"text\": \".$text.\",\n    \"features\": {\n      \"entities\": {\n        \"limit\": 10,\n        \"sentiment\": true\n      },\n      \"categories\": {\n        \"limit\": 1\n    }\n    }\n}";
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS =>$post_data,
                CURLOPT_HTTPHEADER => $header_meta,
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $get_response_in_array = json_decode($response);
            if (!empty($get_response_in_array->entities)) {
                foreach ($get_response_in_array->entities as $key => $entity) {
                    if($entity->type == 'Quantity'){
                        $price = $entity->text;
                    }
                    if($entity->type == 'Location'){
                        $location = $entity->text;
                    }
                    if($entity->type == 'Company'){
                        $company = $entity->text;
                    }
                }
            }
            if (!empty($get_response_in_array->categories)) {
                foreach ($get_response_in_array->categories as $key => $category) {
                    $category_name =  $category->label;  
                }
            }
            return response()->json([
                'status' => true,
                'message' => 'Query successfully executed',
                'Price' => (isset($price)) ? $price : ' ',
                'location'=>(isset($location)) ? $location : ' ',
                'category'=>(isset($category_name)) ? $category_name : ' ',
                'marchant'=>(isset($company)) ? $company : ' '
            ],200);
        }
    }
    //monkey learn 
    public function MonkeyLearn(Request $request){
       $validator = \Validator::make($request->all(), [
            'message' => 'required',]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()],400);
        }else{
            $url = Config::get('constants.Analysers.MonkeyLearnURLEntity');
            $post_data = "{\"data\": [\".$request->message.\"]}";
            $header_meta = array(
                "Authorization: Token ".Config::get('constants.Analysers.MonkeyLearnKEY'),
                "Content-Type: application/json"
            );
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>$post_data,
            CURLOPT_HTTPHEADER => $header_meta,
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $get_response_in_array = json_decode($response);
            
            if (!empty($get_response_in_array[0]->extractions)) {
            foreach ($get_response_in_array[0]->extractions as $key => $array_single_value) {
                if($array_single_value->tag_name == 'LOCATION'){
                    $location = $array_single_value->parsed_value;
                }
                if($array_single_value->tag_name == 'COMPANY'){
                    $company_name = $array_single_value->parsed_value;
                }
            }
            }else{
                $location ='';
                $company_name ='';
            }
            $url = Config::get('constants.Analysers.MonkeyLearnURLCategory');
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>$post_data,
            CURLOPT_HTTPHEADER => $header_meta,
            ));
            $response_price = curl_exec($curl);
            curl_close($curl);
            $get_response_in_array = json_decode($response_price);
            if (!empty($get_response_in_array[0]->extractions)) {
                foreach ($get_response_in_array[0]->extractions as $key => $array_single_value) {
                    if($array_single_value->tag_name == 'PRICE'){
                        $currency = $array_single_value->parsed_value;
                    }
                }  
           }else{
               $currency = '';
           }
            
            return response()->json([
                'status' => true,
                'message' => 'Query successfully executed',
                'Price' => (isset($currency)) ? $currency : ' ',
                'location'=>(isset($location)) ? $location : ' ',
                'marchant'=>(isset($company_name)) ? $company_name : ' '
            ],200);
        }
    }
     //google NL
    public function GoogleNl(Request $request){   
             $validator = \Validator::make($request->all(), [
                 'message' => 'required',]);
 
             if ($validator->fails()) {
                 return response()->json([
                     'status' => false,
                     'message' => $validator->errors()->first()],400);
             }else{
             $cloud = new ServiceBuilder([
                 'keyFilePath' => __DIR__.Config::get('constants.Analysers.GoogleNLUFile'),
                 'projectId' => Config::get('constants.Analysers.GoogleNLUProjectID')
             ]);
             $language = $cloud->language(); 
             $text = $request->message; //'I have spent $10000 on car purchase of Honda from noida';
             $annotation = $language->analyzeEntities($text);
             foreach ($annotation->entities() as $entity) {
                 if($entity['type'] == 'LOCATION'){
                     $location = $entity['name'];
                 }
                 if($entity['type'] == 'PRICE'){
                     $price = $entity['name'];
                 }
                 if($entity['type'] == 'PRICE'){
                     $price = $entity['name'];
                 }
                 if($entity['type'] == 'ORGANIZATION'){
                     $company = $entity['name'];
                 }
                 if($entity['type'] == 'OTHER'){
                     $catogory = $entity['name'];
                 }
             }
             return response()->json([
                 'status' => true,
                 'message' => 'Query successfully executed',
                 'Price' => (isset($price)) ? $price : ' ',
                 'location'=>(isset($location)) ? $location : ' ',
                 'marchant'=>(isset($company)) ? $company : ' ',
                 'catogory' =>(isset($catogory)) ? $catogory : ' '
             ],200);
         }
     }
     //google NL + IBM Watson        
    public function GoogleWatson(Request $request){
         $validator = \Validator::make($request->all(), [
             'message' => 'required',]);
 
         if ($validator->fails()) {
             return response()->json([
                 'status' => false,
                 'message' => $validator->errors()->first()],400);
         }else{
             $text = $request->message;
             $url  = Config::get('constants.Analysers.IBMWatsonURL');
             $header_meta = array(
                 "apikey: ".Config::get('constants.Analysers.IBMWatsonApiKEY'),
                 "Authorization: Basic YXBpa2V5Ol9FQTNwWEVTVF9ESDNEVEJ6UHhIeUR6SnBXRDAxN2kxZ2xELWxqemVnLWdS",
                 "Content-Type: application/json"
             );
             $rule = 
             $post_data = "{\n    \"text\": \".$text.\",\n    \"features\": {\n      \"entities\": {\n        \"limit\": 10,\n        \"sentiment\": true\n      },\n      \"categories\": {\n        \"limit\": 1\n    }\n    }\n}";
             $curl = curl_init();
             curl_setopt_array($curl, array(
                 CURLOPT_URL => $url,
                 CURLOPT_RETURNTRANSFER => true,
                 CURLOPT_ENCODING => "",
                 CURLOPT_MAXREDIRS => 10,
                 CURLOPT_TIMEOUT => 0,
                 CURLOPT_FOLLOWLOCATION => true,
                 CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                 CURLOPT_CUSTOMREQUEST => "POST",
                 CURLOPT_POSTFIELDS =>$post_data,
                 CURLOPT_HTTPHEADER => $header_meta,
             ));
 
             $response = curl_exec($curl);
             curl_close($curl);
             $get_response_in_array = json_decode($response);
             $cloud = new ServiceBuilder([
                 'keyFilePath' => __DIR__.Config::get('constants.Analysers.GoogleNLUFile'),
                 'projectId' => Config::get('constants.Analysers.GoogleNLUProjectID')
             ]);
             $language = $cloud->language();
             $annotation = $language->analyzeEntities($text);
             foreach ($annotation->entities() as $entity) {
                 if($entity['type'] == 'PRICE'){
                     $price = $entity['name'];
                 }
                 if($entity['type'] == 'ORGANIZATION'){
                     $company = $entity['name'];
                 }
             }
             if (!empty($get_response_in_array->entities)) {
                 foreach ($get_response_in_array->entities as $key => $entity) {
                     if($entity->type == 'Location'){
                         $location = $entity->text;
                     }
                 }
             }
             if (!empty($get_response_in_array->categories)) {
                 foreach ($get_response_in_array->categories as $key => $category) {
                     $category_name =  $category->label;  
                 }
             }
             return response()->json([
                 'status' => true,
                 'message' => 'Query successfully executed',
                 'Price' => (isset($price)) ? $price : ' ',
                 'location'=>(isset($location)) ? $location : ' ',
                 'category'=>(isset($category_name)) ? $category_name : ' ',
                 'marchant'=>(isset($company)) ? $company : ' '
             ],200);
         }
    }
}
