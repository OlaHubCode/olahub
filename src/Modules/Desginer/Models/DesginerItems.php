<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class DesignerItems extends Model
{
    protected $table = 'designer_items';

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        static::addGlobalScope('published', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('is_published', '1');
        });
    }

    static $columnsMapping = [
        'categories' => [
            'column' => 'category_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'numeric'
        ],
        'desginers' => [
            'column' => 'designer_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'numeric'
        ],
        'classifications' => [
            'column' => 'clasification_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'numeric'
        ],
        'occasions' => [
            'column' => 'occasion_id',
            'type' => 'number',
            'relation' => 'occasions',
            'validation' => 'numeric'
        ],
        'categorySlug' => [
            'column' => 'category_slug',
            'type' => 'number',
            'relation' => 'category',
            'validation' => 'numeric'
        ],
        'classificationSlug' => [
            'column' => 'class_slug',
            'type' => 'number',
            'relation' => 'classification',
            'validation' => 'numeric'
        ],
        'desginerSlug' => [
            'column' => 'designer_slug',
            'type' => 'number',
            'relation' => 'designer',
            'validation' => 'numeric'
        ],
        'occasionSlug' => [
            'column' => 'occasion_slug',
            'type' => 'number',
            'relation' => 'occasionSync',
            'validation' => 'numeric'
        ],
        'interestSlug' => [
            'column' => 'interest_slug',
            'type' => 'number',
            'relation' => 'interestSync',
            'validation' => 'numeric'
        ],
    ];

    static function checkPrice($item, $final = false, $withCurr = true)
    {
        $return["productPrice"] = isset($item->price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->price, $withCurr) : 0;
        $return["productDiscountedPrice"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->price, $withCurr);
        $return["productHasDiscount"] = false;
        if (isset($item->discounted_price) && $item->discounted_price && strtotime($item->discounted_price_start_date) <= time() && strtotime($item->discounted_price_end_date) >= time()) {
            $return["productPrice"] = isset($item->discounted_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->discounted_price, $withCurr) : 0;
            $return["productHasDiscount"] = true;
        }

        if ($final) {
            return $return["productDiscountedPrice"];
        }
        return $return;
    }

    static function searchItem($q = 'a', $count = 15)
    {
        $items = DesignerItems::where('name', 'LIKE', "%$q%")
            ->whereNull("parent_item_id")
            ->orWhere("parent_item_id", 0);
        // $items = DesignerItems::where('name', 'LIKE', "%$q%")->orWhereRaw('name sounds like ?', $q)
        //     ->where(function ($q) {
        //         $q->whereNull("parent_item_id");
        //         $q->orWhere("parent_item_id", 0);
        //     });
        if ($count > 0) {
            return $items->paginate($count);
        } else {
            return $items->count();
        }
    }

    public function images()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\DesignerItemImages', 'item_id');
    }

    public function interests()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\DesignerItemInterests', 'item_id');
    }

    public function interestSync()
    {
        return $this->belongsToMany('OlaHub\UserPortal\Models\Interest', 'designer_item_interests', 'item_id', 'interest_id');
    }

    public function occasions()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\DesignerItemOccasions', 'item_id');
    }

    public function occasionSync()
    {
        return $this->belongsToMany('OlaHub\UserPortal\Models\Occasion', 'designer_item_occasions', 'item_id', 'occasion_id');
    }

    public function category()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\ItemCategory', 'category_id');
    }

    public function classification()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\Classification', 'clasification_id');
    }
    public function designer()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\Designer', 'designer_id');
    }

    public function valuesData()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\DesignerItemAttrValue', 'item_id');
    }
}
