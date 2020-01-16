<?php

namespace OlaHub\UserPortal\Models;

class Currency extends \Illuminate\Database\Eloquent\Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }
    protected $table = 'lkp_currencies';

    
    public function countries(){
        return $this->hasMany('OlaHub\UserPortal\Models\Country', 'currency_id');
    }
    
    static function preRequestData() {
        $return = [];
        $data = Currency::all();
        foreach ($data as $one) {
            $return[] = [
                'value' => $one->id,
                'text' => $one->name,
            ];
        }
        return $return;
    }
}
