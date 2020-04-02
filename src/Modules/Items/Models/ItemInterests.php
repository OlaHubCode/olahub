<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class ItemInterests extends Model
{

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        static::addGlobalScope('country', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->whereHas('itemsMainData', function ($itemQ) {
                $itemQ->whereHas('merchant', function ($merQ) {
                    $merQ->where('country_id', app('session')->get('def_country')->id);
                });
            });
        });
    }

    protected $table = 'catalog_item_interests';

    public function itemsMainData()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem', 'item_id');
    }

    public function interestMainData()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\Interests', 'interest_id');
    }
}
