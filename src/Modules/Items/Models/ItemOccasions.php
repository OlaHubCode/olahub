<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class ItemOccasions extends Model {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);

        static::addGlobalScope('country', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->whereHas('itemsMainData', function ($itemQ) {
                $itemQ->whereHas('merchant', function ($merQ) {
                    $merQ->where('country_id', app('session')->get('def_country')->id);
                });
            });
        });
    }

    protected $table = 'catalog_item_occasions';

    public function itemsMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem', 'item_id');
    }

    public function occasionMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Occasion', 'occasion_id');
    }

}
