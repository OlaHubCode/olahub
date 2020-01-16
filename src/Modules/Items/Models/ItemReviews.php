<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class ItemReviews extends Model {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        
        static::saving(function ($builder){
            $builder->user_id = app('session')->get('tempID');
        });
    }
    protected $table = 'catalog_item_reviews';

    public function itemMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem', 'item_id');
    }

    public function userMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'user_id');
    }

}
