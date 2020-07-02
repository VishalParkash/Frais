<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Category;
use Validator;
use App\Traits\AwsTrait;

class CategoryController extends Controller
{
	use AwsTrait;
    public function Category($CategoryId=false) {
    	$this->id = Auth::user()->id;
        $requestData = trim(file_get_contents("php://input"));
        $userRequestValidate = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        if(empty($CategoryId)){
        		$validateCategoryName = 'required|string|unique:categories';
        	}else{
        		$validateCategoryName = 'required|string|unique:categories,category,'.$CategoryId;
        	}
        	$validator = Validator::make($userRequestValidate, [
	            'category' => $validateCategoryName,
	        ]);

        	if($validator->fails()){
            return response()->json([
                'status' => false,
                'result' => null,
                // 'error' => $validator->errors()
                'message' => $validator->errors()->first()
            ]);      
        }
        if(!empty($CategoryId)){
        	$Category = Category::find($CategoryId);
        }else{
        	$Category = new Category();
        }
        
        if(!is_null($Category)){
            if(!empty($userRequest->icon)){
                $Category->icon = $userRequest->icon;
            }else{
                $Category->icon = null;
            }
            
            // if($this->validateCategory($this->id, $userRequest->category)){
            //     $response['status'] = false;
            //     $response['message'] = "You have already created this category.";
            //     return $response;
            // }

            $Category->category = $userRequest->category;
            $Category->user_id = $this->id;
            try{
            	if($Category->save()){
                $response['status'] = true;
                $response['message'] = 'Category added successfully';
                $response['result'] = $Category;

            }else{
                $response['status'] = false;
                $response['message'] = 'Something went wrong. Please try again.';
            }
        }catch(\Exception $ex){
        	$response['status'] = false;
            $response['message'] = 'Something went wrong. Please try again.';
        }
            
        }else{
            $response['status'] = false;
            $response['message'] = 'Something went wrong. Please contact administrator.';
        }

        return $response;
    }

    public function categories(){
        // $Category = Category::where('user_id', '=', $this->id)->get();
        $this->id = Auth::user()->id;
        $Category = Category::select('id','icon','category')
                        ->where('user_id', $this->id)
                        // ->orWhere('categoryType', 'default')
                        ->get();

        if(!empty($Category)){
            $Category = $Category->toArray();
            $Categories = array();
            foreach($Category as $category){

                $category['icon'] = $this->getImageFromS3($category['id'], 'Category');
                $Categories[] = $category;
            }
            $response['status'] = true;
            $response['result'] = $Categories;
            $response['message'] = "success";
        }else{
            $response['status'] = false;
            $response['result'] = null;
            $response['message'] = "No categories available. Please create new";
        }

        return $response;
    }
}
