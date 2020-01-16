<?php

/**
 * MerGeneralInfos model 
 * To connect with database and make all queries  
 * all functions return with eloqouent object or array of objects
 * 
 * @author Mohamed EL-Absy <mohamed.elabsy@yahoo.com>
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0 
 */

namespace OlaHub\UserPortal\Models;

class MerchantCategory extends \Illuminate\Database\Eloquent\Model {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }

    protected $table = 'merchant_item_categories';
    protected $columnsMaping = [
        
    ];
    
    public function catCountry() {
        return $this->belongsTo('OlaHub\UserPortal\Models\ManyToMany\ItemCountriesCategory', 'category_id');
    }
    
    public function merchant(){
        return $this->belongsTo('OlaHub\UserPortal\Models\Merchant', 'merchant_id');
    }
}
