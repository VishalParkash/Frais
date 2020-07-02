<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Country;
use App\Currency;
use App\Traits\AwsTrait;

class CountryController extends Controller
{
    // public function addCountry(){
    // 	$requestData = trim(file_get_contents("php://input"));
    //     $userRequest = (json_decode($requestData));

    //     $Country = new Country();
    //     if(!is_null($Country)){
    //     	$Country->flag = $userRequest->flag;
    //     	$Country->code = $userRequest->code;
    //     	$Country->name = $userRequest->name;

    //     	try{
    //     		$Country->save();
    //     		$Country->flag = $this->getImageFromS3($category['id'], 'Category');
    //     		$response['status'] = true;
    //     		$response['message'] = "Data saved";
    //     		// $response['result'] = ;
    //     	}
    //     }
    // }
		use AwsTrait;
        public function countries(){
        // $Category = Category::where('user_id', '=', $this->id)->get();
        // $this->id = Auth::user()->id;
        $Country = Country::all();
        // $Category = Category::select('id','icon','category')
        //                 ->where('user_id', $this->id)
        //                 // ->orWhere('categoryType', 'default')
        //                 ->get();
        // print_r($Country);die;
        if(!empty($Country)){
            $Country = $Country->toArray();
            $Countries = array();
            foreach($Country as $country){

                $country['flag'] = $this->getImageFromS3($country['id'], 'Country');
                $Countries[] = $country;
            }
            $response['status'] = true;
            $response['result'] = $Countries;
            $response['message'] = "success";
        }else{
            $response['status'] = false;
            $response['result'] = null;
            $response['message'] = "No categories available. Please create new";
        }

        return $response;
    }

    public function currencies(){
        $Currency = Currency::get();
        if(!empty($Currency)){
            $Currency = $Currency->toArray();
            foreach($Currency as $currency){
                // $currency['CurrencyIcon'] = 
                $currency['CurrencyIcon'] = $this->getImageFromS3($currency['id'], 'Currencies');
                $Currencies[] = $currency;
            }
            $response['status'] = true;
            $response['currencies'] = $Currencies;
        }else{
            $response['status'] = false;
            $response['message'] = "No currency found.";
        }
        return $response;
    }
}
