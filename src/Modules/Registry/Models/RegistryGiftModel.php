<?php

namespace OlaHub\UserPortal\Models;
use Illuminate\Database\Eloquent\Model;

class RegistryGiftModel extends Model {

    protected $table = 'registries_items';
    
    static $columnsMaping = [

        'registryId' => [
            'column' => 'registry_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|exists:registries,id'
        ],
        'registryItem' => [
            'column' => 'item_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required'
        ],
        'registryItemQuantity' => [
            'column' => 'quantity',
            'type' => 'number',
            'relation' => false,
            'validation' => 'numeric'
        ],
        'registryItemType' => [
            'column' => 'item_type',
            'type' => 'string',
            'relation' => false,
            'validation' => 'string'
        ],
        
        
    ];
    
    public function registry(){
        return $this->belongsTo('OlaHub\UserPortal\Models\RegistryModel','registry_id');
    }

    public function creatorUser() {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'created_by');
    }
    
}
