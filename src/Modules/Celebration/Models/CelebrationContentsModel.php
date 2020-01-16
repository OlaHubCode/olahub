<?php

namespace OlaHub\UserPortal\Models;
use Illuminate\Database\Eloquent\Model;

class CelebrationContentsModel extends Model {

    protected $table = 'celebration_contents';
    
    static $columnsMaping = [
        
        'celebrationWishText' => [
            'column' => 'title',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:200'
        ],
        'celebrationVideo' => [
            'column' => 'reference',
            'type' => 'string',
            'relation' => false,
        ],
        'celebrationUser' => [
            'column' => 'created_by',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|numeric|exists:celebration_participants,id'
        ],
        'celebrationId' => [
            'column' => 'celebration_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|numeric|exists:celebrations,id'
        ],
        
        
    ];
  
}
