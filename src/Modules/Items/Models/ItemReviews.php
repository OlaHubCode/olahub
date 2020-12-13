<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemReviews extends Model {
    use SoftDeletes;

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }
    protected $table = 'catalog_item_reviews';

    public function itemMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem', 'item_id');
    }

    public function userMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'user_id');
    }

}
