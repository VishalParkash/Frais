<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Chat;
use App\User;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\Response;
Use \Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Database\Transaction;

class ChatController extends Controller
{
    public function UserList() {
        
        $loggedIn = Auth::user()->id; 
        $loggedInType = Auth::user()->userType; 
        // $User = User::all();
        // $response['status'] = true;
        // $response['result'] = $User;

        if(($loggedInType== 'admin') || ($loggedInType == 'accountant')){
            $collection_name = 'messages/';
        }else{
            $request['sender_id'] = $loggedIn; //user loggedIn receiver
            $request['receiver_id'] = 7; //user loggedIn receiver
  
        $chat_id = ($request['sender_id'] < $request['receiver_id']) ? $request['sender_id'].'_'.$request['receiver_id'] : $request['receiver_id'].'_'.$request['sender_id'];
            $collection_name = 'messages/';
        }
        
        $factory = (new Factory)->withServiceAccount(__DIR__.Config::get('constants.Chats.firbaseUrl'));
        $database = $factory->createDatabase();
        $chatdata =   $database->getReference($collection_name)->getvalue();
        // echo "<pre>";print_r($chatdata);die;
        $Users =  array();
        if(!empty($chatdata)){
            foreach($chatdata as $Keydata){

                foreach($Keydata as $data){
                    // echo "<pre>";print_r($data);
                    if(!empty($data['msg_key'])){
                        // $data['msgStatus'] = $database->getReference($collection_name.'/'.$data['msg_key'])->getvalue('msgStatus');
                        $Users[] = $data;        
                    }
                }
                
            }
        }

        // echo "<pre>";print_r($NewData);
        $response['result'] = $Users;
        return $response;
        
    }

    public function message()
    {
    	$requestData = trim(file_get_contents("php://input"));
        $request = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $request['sender_id'] = Auth::user()->id; //user loggedIn sender

        $validator = Validator([
            'receiver_id' => 'required',
            'message' => 'required',
        ]);
        
        if($validator->fails()){
            $response['status'] = false;
            $response['message'] = null;
            return $response;
        }

        $chat_id = ($request['sender_id'] < $request['receiver_id']) ? $request['sender_id'].'_'.$request['receiver_id'] : $request['receiver_id'].'_'.$request['sender_id'];
        $messages = "messages/".$chat_id;
        $User = User::find($request['sender_id'], ['name']);
        $User->_id = $request['sender_id'];

        if(!empty($request['file'])){
            $base64_image = $request['file'];
            $data = substr($base64_image, strpos($base64_image, ',') + 1);
            $data = base64_decode($data);
            $file_name = 'image_' . time() . '.jpg';
            Storage::disk('local')->put($file_name, $data);
        }

        $firebase_data_array = [
                    '_id' =>  $chat_id,
                    'text'  =>  $request['message'],
                    'user' => $User,
                    'msgStatus' => 'unread',
                    'media_url' => (!empty($file_name)) ? ($file_name) : (''),
                    'createdAt' => Carbon::now()->toDateTimeString(),
                ];
    	$factory = (new Factory)->withServiceAccount(__DIR__.Config::get('constants.Chats.firbaseUrl'));
        $database = $factory->createDatabase();
        $createPost =   $database
                            ->getReference($messages)
                            ->push($firebase_data_array);
        $msg_key = $createPost->getKey();
        $database->getReference($messages.'/'.$msg_key)->update(array('msg_key' => $msg_key));
        $firebase_data_array['msg_key'] = $msg_key;
        if($createPost){
            return Response()->json([
                'status' => true,
                'message' => 'sent',
                'result' => $firebase_data_array
            ], 200);
        }else{
            return Response()->json([
                'status' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    public function messages($receiver_id) {

        $requestData = trim(file_get_contents("php://input"));
        $request = (json_decode($requestData, TRUE));
        $userRequest = (json_decode($requestData));

        $request['sender_id'] = Auth::user()->id; //user loggedIn sender
        $request['receiver_id'] = $receiver_id; //user loggedIn receiver
  
        $chat_id = ($request['sender_id'] < $request['receiver_id']) ? $request['sender_id'].'_'.$request['receiver_id'] : $request['receiver_id'].'_'.$request['sender_id'];
        $collection_name = 'messages/'.$chat_id;
        $factory = (new Factory)->withServiceAccount(__DIR__.Config::get('constants.Chats.firbaseUrl'));
        $database = $factory->createDatabase();
        $chatdata =   $database->getReference($collection_name)->getvalue();
        $NewData =  array();
        if(!empty($chatdata)){
            foreach($chatdata as $data){
                if(!empty($data['msg_key'])){
                    // echo $data['msg_key'];
                    $database->getReference($collection_name.'/'.$data['msg_key'])->update(array('msgStatus' => 'read'));
                }
                $data['media_url'] = asset('storage/app/'.$data['media_url']);
                $NewData[] = $data;
            }
        }
        if(!empty($NewData)){
            $Data = $NewData;
        }else{
            $Data = array();
        }
        
        return response()->json(['status' => true, 'result'=> $Data],200);
        
        
    }
}
