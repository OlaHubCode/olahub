<?php

namespace OlaHub\UserPortal\Models;
use Illuminate\Database\Eloquent\Model;

class CalendarModel extends Model {

    protected $table = 'users_calendar';
    
    static $columnsMaping = [
        
        'calendarTitle' => [
            'column' => 'title',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|max:20'
        ],
        'calendarOccassion' => [
            'column' => 'occasion_id',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|exists:occasion_types,id'
        ],
        'calendarDate' => [
            'column' => 'calender_date',
            'type' => 'string',
            'relation' => false,
            'validation' => 'date'
        ],
        'calendarAnnual' => [
            'column' => 'is_annual',
            'type' => 'string',
            'relation' => false,
            'validation' => ''
        ],
        
    ];
    
    
    public function occasion()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\Occasion','occasion_id');
    }
    
    
    /*public function getColumns($requestData,$calendar = false){
        if($user){
            $array = $calendar;
        }else{
            $array= new \stdClass;
        }
        
        foreach ($requestData as $key => $value){
            if(isset($this->columnsMaping[$key]['column'])){
                $array->{$this->columnsMaping[$key]['column']} = $value;
            }
        }
        return $array;
    }*/
}
