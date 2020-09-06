<?php

namespace OlaHub\UserPortal\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CelebrationParticipantsModel extends Model {

    use SoftDeletes;

    protected $table = 'celebration_participants';
    
    static $columnsMaping = [
        
        'userId' => [
            'column' => 'user_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|exists:users,id'
        ],
        'celebrationId' => [
            'column' => 'celebration_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|exists:celebrations,id'
        ],
        
        
    ];
    
    public function celebration(){
        return $this->belongsTo('OlaHub\UserPortal\Models\CelebrationModel','celebration_id');
    }
    
    
}
