<?php

namespace OlaHub\UserPortal\Models;
use Illuminate\Database\Eloquent\Model;

class UserSessionModel extends Model {

    protected $table = 'user_sessions';
    
    protected $columnsMaping = [
        
    ];
    
    
    
    public function getColumns($requestData,$user = false){
        if($user){
            $array = $user;
        }else{
            $array= new \stdClass;
        }
        
        foreach ($requestData as $key => $value){
            if(isset($this->columnsMaping[$key]['column'])){
                $array->{$this->columnsMaping[$key]['column']} = $value;
            }
        }
        return $array;
    }
    
    public function user()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel');
    }
    
}
