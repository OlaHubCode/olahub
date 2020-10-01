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

    public function exchangePolicy()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\ExchangeAndRefund', 'exchange_refund_policy');
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

    static function checkIsNew($data)
    {
        if ($data && $data instanceof DesignerItems) {
            $createTime = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($data->created_at, "Y-m-d");
            $maxDays = date("Y-m-d", strtotime("-2 Days"));
            if ($createTime >= $maxDays) {
                return true;
            }
        }
        return false;
    }

    static function checkPrice($item, $final = false, $withCurr = true)
    {
        $return["productPrice"] = isset($item->price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->price, $withCurr) : 0;
        $return["productDiscountedPrice"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->price, $withCurr);
        $return["productHasDiscount"] = false;
        if (isset($item->discounted_price) && $item->discounted_price && strtotime($item->discounted_price_start_date) <= time() && strtotime($item->discounted_price_end_date) >= time()) {
            $return["productDiscountedPrice"] = isset($item->discounted_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->discounted_price, $withCurr) : 0;
            $return["productHasDiscount"] = true;
        }

        if ($final) {
            return $return["productDiscountedPrice"];
        }
        return $return;
    }

    static function searchItem($text = 'a', $count = 15)
    {
        $words = explode(" ", $text);

        $items = DesignerItems::where(function ($query) {
            $query->whereNull('parent_item_id');
            $query->orWhere('parent_item_id', '0');
        });

        $items->where(function ($q) use($words) {

            $q->where(function ($q1) use($words) {
                foreach ($words as $word){
                    $q1->whereRaw('FIND_IN_SET(?, REPLACE(description, " ", ","))', $word);
                }
            });
            $q->orWhere(function ($q2) use($words) {
                foreach ($words as $word){
                    $q2->whereRaw('FIND_IN_SET(?, REPLACE(name, " ", ","))', $word);
                }
            });
            $q->orWhere(function ($q3) use($words) {
                foreach ($words as $word){
                    $length = strlen($word);
                    if($length >= 3){
                        $firstWords = substr($word, 0,3);
                        $q3->whereRaw('LOWER(`name`) LIKE ? ','%' . $firstWords . '%');

                        if($length >= 6){
                            $lastWords = substr($word, -3);
                            $q3->WhereRaw('LOWER(`name`) LIKE ? ','%' . $lastWords . '%');
                        }
                    }else if($length == 2){
                        $q3->whereRaw('LOWER(`name`) LIKE ? ','%' . $word . '%');
                    }
                }
            });
            $q->orWhere(function ($q3) use($words) {
                foreach ($words as $word){
                    $length = strlen($word);
                    if($length >= 3){
                        $firstWords = substr($word, 0,3);
                        $q3->whereRaw('LOWER(`description`) LIKE ? ','%' . $firstWords . '%');

                        if($length >= 6){
                            $lastWords = substr($word, -3);
                            $q3->WhereRaw('LOWER(`description`) LIKE ? ','%' . $lastWords . '%');
                        }
                    }else if($length == 2){
                        $q3->whereRaw('LOWER(`description`) LIKE ? ','%' . $word . '%');
                    }
                }
            });
        });

        $items->orWhere('description', '=',$text);
        $items->orWhere('name', '=', $text);

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
        return $this->belongsToMany('OlaHub\UserPortal\Models\Interests', 'designer_item_interests', 'item_id', 'interest_id');
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
    public function templateItem()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\DesignerItems', 'parent_item_id');
    }
}
