<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\HybridRelations;

class CatalogItem extends Model {

    use HybridRelations;

    protected $connection = 'mysql';

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);

        static::addGlobalScope('published', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('is_published', '1');
        });

        static::addGlobalScope('country', function (\Illuminate\Database\Eloquent\Builder $builder) {

            $builder->whereHas('merchant', function ($merchantQ) {
                $merchantQ->where('country_id', app('session')->get('def_country')->id);
            });
        });
    }

    protected $table = 'catalog_items';
    static $columnsMaping = [
        //Main table
        'categories' => [
            'column' => 'category_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'numeric'
        ],
        'brands' => [
            'column' => 'store_id',
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
        //Relations tables
//        'attributes' => [
//            'column' => 'item_attribute_value_id',
//            'type' => 'number',
//            'relation' => 'valuesData',
//            'validation' => 'numeric'
//        ],
        'occasions' => [
            'column' => 'occasion_id',
            'type' => 'number',
            'relation' => 'occasions',
            'validation' => 'numeric'
        ],
        //Slugs
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
        'brandSlug' => [
            'column' => 'store_slug',
            'type' => 'number',
            'relation' => 'brand',
            'validation' => 'numeric'
        ],
        'occasionSlug' => [
            'column' => 'occasion_slug',
            'type' => 'number',
            'relation' => 'occasionSync',
            'validation' => 'numeric'
        ],
        'merchantSlug' => [
            'column' => 'merchant_slug',
            'type' => 'number',
            'relation' => 'merchant',
            'validation' => 'numeric'
        ],
        'interestSlug' => [
            'column' => 'interest_slug',
            'type' => 'number',
            'relation' => 'interests',
            'validation' => 'numeric'
        ],
    ];

    public function country() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Country');
    }

    public function templateItem() {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem', 'parent_item_id');
    }

    public function interests() {
        return $this->hasMany('OlaHub\UserPortal\Models\Interests', 'items');
    }

    public function merchant() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Merchant', 'merchant_id');
    }

    public function images() {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemImages', 'item_id');
    }

    public function brand() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Brand', 'store_id');
    }

    public function category() {
        return $this->belongsTo('OlaHub\UserPortal\Models\ItemCategory', 'category_id');
    }

    public function classification() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Classification', 'clasification_id');
    }

    public function occasions() {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemOccasions', 'item_id');
    }

    public function occasionSync() {
        return $this->belongsToMany('OlaHub\UserPortal\Models\Occasion', 'catalog_item_occasions', 'item_id', 'occasion_id');
    }

    public function valuesData() {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemAttrValue', 'item_id');
    }

    public function parentValuesData() {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemAttrValue', 'parent_item_id');
    }

    public function exchangePolicy() {
        return $this->belongsTo('OlaHub\UserPortal\Models\ExchangeAndRefund', 'exchange_refund_policy');
    }

    public function reviewsData() {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemReviews', 'item_id');
    }

    static function checkStock($data) {
        if ($data && $data instanceof CatalogItem) {
            $quantity = 0;
            $itemQ = ItemPickuAddr::selectRaw('SUM(quantity) as qu')->where('item_id', $data->id)->first();
            if ($itemQ->qu) {
                $quantity = $itemQ->qu;
            }
            return $quantity;
        }
    }

    static function checkIsNew($data) {
        if ($data && $data instanceof CatalogItem) {
            $createTime = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($data->created_at, "Y-m-d");
            $maxDays = date("Y-m-d", strtotime("-2 Days"));
            if ($createTime >= $maxDays) {
                return true;
            }
        }
        return false;
    }

    static function checkPrice(CatalogItem $data, $final = false, $withCurr = true, $countryId = false) {
        $return["productPrice"] = isset($data->price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($data->price, $withCurr, $countryId) : 0;
        $return["productDiscountedPrice"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($final ? $data->price : 0, $withCurr, $countryId);
        $return["productHasDiscount"] = false;
        if ($data->has_discount && strtotime($data->discounted_price_start_date) <= time() && strtotime($data->discounted_price_end_date) >= time()) {
            $return["productDiscountedPrice"] = isset($data->discounted_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($data->discounted_price, $withCurr, $countryId) : 0;
            $return["productHasDiscount"] = true;
        }

        if ($final) {
            return $return["productDiscountedPrice"];
        }
        return $return;
    }

    static function searchItem($q = 'a', $count = 15) {
        $items = CatalogItem::where('name', 'LIKE', "%$q%")
                        ->whereHas("merchant", function($merQ) {
                            $merQ->where('country_id', app('session')->get('def_country')->id);
                        })->where(function($q) {
            $q->whereNull("parent_item_id");
            $q->orWhere("parent_item_id", 0);
        });
        if ($count > 0) {
            return $items->paginate($count);
        } else {
            return $items->count();
        }
    }

    static function searchItemByClassification($q = 'a', $classification = false, $count = 15) {
        if ($classification) {
            $items = (new CatalogItem)->newQuery();
            $items->where('name', 'LIKE', "%$q%");
            $items->whereHas("merchant", function($merQ) {
                $merQ->where('country_id', app('session')->get('def_country')->id);
            });
            $items->whereHas("classification", function($merQ) use($classification) {
                $merQ->where('class_slug', $classification);
            });
            $items->where(function($q) {
                $q->whereNull("parent_item_id");
                $q->orWhere("parent_item_id", 0);
            });

            return $items->paginate($count);
        }
        return false;
    }

    static function getItemIdsFromInterest($interests) {
        $return = [];
        foreach ($interests as $interest) {
            $itemsIDs = $interest->items;
            foreach ($itemsIDs as $id) {
                if (!in_array($id, $return)) {
                    $return[] = $id;
                }
            }
        }
        return $return;
    }

}
