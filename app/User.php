<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Traits\UuidTrait;



class User extends Authenticatable
{
    use Notifiable, HasApiTokens, UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','userDeviceId'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // public function getUserByMobile($number){
    //     $User = User::where('mobileNumber', $number)->first();
    //     if(!empty($User)){
    //         return $User->id;
    //     }else{
    //         return false;
    //     }
    // }

    public function getUserByColumn($Parameter, $value){
        $User = User::where($Parameter, $value)->first();
        if(!empty($User)){
            return $User;
        }else{
            return false;
        }
    }

    public function categories(){
        return $this->hasMany(Category::class);
    }
}
