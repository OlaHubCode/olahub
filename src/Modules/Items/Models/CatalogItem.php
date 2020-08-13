<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogItem extends Model
{
    protected $connection = 'mysql';

    public function __construct(array $attributes = array())
    {
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
            'relation' => 'interestSync',
            'validation' => 'numeric'
        ],
    ];

    public function country()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\Country');
    }

    public function templateItem()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem', 'parent_item_id');
    }

    public function interests()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemInterests', 'item_id');
    }

    public function interestSync()
    {
        return $this->belongsToMany('OlaHub\UserPortal\Models\Interests', 'catalog_item_interests', 'item_id', 'interest_id');
    }

    public function merchant()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\Merchant', 'merchant_id');
    }

    public function images()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemImages', 'item_id');
    }

    public function brand()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\Brand', 'store_id');
    }

    public function category()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\ItemCategory', 'category_id');
    }

    public function classification()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\Classification', 'clasification_id');
    }

    public function occasions()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemOccasions', 'item_id');
    }

    public function occasionSync()
    {
        return $this->belongsToMany('OlaHub\UserPortal\Models\Occasion', 'catalog_item_occasions', 'item_id', 'occasion_id');
    }

    public function valuesData()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemAttrValue', 'item_id');
    }

    public function parentValuesData()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemAttrValue', 'parent_item_id');
    }

    public function exchangePolicy()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\ExchangeAndRefund', 'exchange_refund_policy');
    }

    public function reviewsData()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemReviews', 'item_id');
    }

    public function quantityData()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemPickuAddr', 'item_id');
    }

    static function checkStock($data)
    {
        if ($data && $data instanceof CatalogItem) {
            $quantity = 0;
            $itemQ = ItemPickuAddr::selectRaw('SUM(quantity) as qu')->where('item_id', $data->id)->first();
            if ($itemQ->qu) {
                $quantity = $itemQ->qu;
            }
            return $quantity;
        }
    }

    static function checkIsNew($data)
    {
        if ($data && $data instanceof CatalogItem) {
            $createTime = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($data->created_at, "Y-m-d");
            $maxDays = date("Y-m-d", strtotime("-2 Days"));
            if ($createTime >= $maxDays) {
                return true;
            }
        }
        return false;
    }

    static function checkPrice(CatalogItem $data, $final = false, $withCurr = true, $countryId = false)
    {
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
    static function searchItem($text = 'a', $count = 15)
    {
        $text_length = strlen($text) - substr_count($text, ' ');

        $text = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::replaceSpecChars($text);
        $words = explode(" ", $text);
        $words = array_filter($words, function ($word) {
            return !empty($word);
        });

        $words = array_filter($words, function ($word) {
            $patterns     = array( "/(ا|إ|أ|آ)/", "/(ه|ة)/" );
            $replacements = array( "[ا|إ|أ|آ]", "[ه|ة]" );

            return preg_replace($patterns, $replacements, $word);

        });
var_dump($words);return " ";
        $items = CatalogItem::where(function ($query) {
            $query->whereNull('parent_item_id');
            $query->orWhere('parent_item_id', '0');
        });

        //        $findInAttribute = CatalogItem::where(function ($query) {
        //            $query->whereNull('parent_item_id');
        //            $query->orWhere('parent_item_id', '0');
        //            })->WhereHas("valuesData", function ($q3) use ($words) {
        //            $q3->WhereHas("valueMainData",function ($q4) use ($words){
        //                $q4->where(function ($q1) use($words) {
        //                    foreach ($words as $key => $word){
        //                        if($key == 0){
        //                            $q1->Where('attribute_value', 'like', '%' . $word . '%');
        //
        //                        }else{
        //                            $q1->orWhere('attribute_value', 'like', '%' . $word . '%');
        //                        }
        //                    }
        //                });
        //            });
        //        })->count();



        $find = CatalogItem::where(function ($query) {
            $query->whereNull('parent_item_id');
            $query->orWhere('parent_item_id', '0');
        })
            ->where(function ($q) use ($words) {
                //search in categories
                //                $q->orWhereHas("category", function ($q3) use ($words) {
                //                    $q3->Where(function ($q2) use($words) {
                //                        foreach ($words as $word){
                //                            $q2->whereRaw('FIND_IN_SET(?, REPLACE(category_slug, "-", ","))', $word);
                //                        }
                //                    });
                //                    //search in parent's categories
                //                    $q3->orWhereHas("parentCategory",function ($q4) use ($words){
                //                        foreach ($words as $word){
                //                            $q4->whereRaw('FIND_IN_SET(?, REPLACE(category_slug, "-", ","))', $word);
                //                        }
                //                    });
                //                });

                //search in occasions
                $q->whereHas("occasions", function ($q3) use ($words) {
                    $q3->WhereHas("occasionMainData", function ($q4) use ($words) {
                        $occQuery = [];
                        $occQuery2 = [];
                        foreach ($words as $word) {

                            array_push($occQuery, "replace(LOWER(JSON_EXTRACT(name, '$.en')), '\'', '') like Lower('%" . $word . "%') ");
                            array_push($occQuery2, "replace(LOWER(JSON_EXTRACT(name, '$.ar')), '\'', '') like Lower('%" . $word . "%') ");
                            
                        }
                        $q4->whereRaw(join('and ', $occQuery));
                        $q4->orWhereRaw(join('and ', $occQuery2));

                    });
                    
                });

                //search in classification
                //                $q->orWhereHas("classification", function ($q3) use ($words) {
                //                    foreach ($words as $word){
                //                        $q3->whereRaw('FIND_IN_SET(?, REPLACE(class_slug, "-", ","))', $word);
                //                    }
                //                });
                //
                //                //search in interests
                //                $q->orWhereHas("interests", function ($q3) use ($words) {
                //                    $q3->WhereHas("interestMainData",function ($q4) use ($words){
                //                        foreach ($words as $word){
                //                            $q4->whereRaw('FIND_IN_SET(?, REPLACE(interest_slug, "-", ","))', $word);
                //                        }
                //                    });
                //                });

            })
            ->orWhere('description', '=', $text)
            ->orWhere('name', '=', $text);
        //        ->orWhere('name', '=', $text)->count();

        //        $items->where(function ($q) use($words,$find) {

        //            $q->Where(function ($q2) use($words) {
        //                foreach ($words as $word){
        //                    $q2->whereRaw('FIND_IN_SET(?, REPLACE(name, " ", ","))', $word);
        //                }
        //            });

        //            $q->orWhereHas("category", function ($q3) use ($words,$find) {
        //                $q3->Where(function ($q2) use($words,$find) {
        //                    foreach ($words as $word){
        //                        if($find !== 0) {
        //                            $q2->whereRaw('FIND_IN_SET(?, REPLACE(category_slug, "-", ","))', $word);
        //                        }elseif($word != 'and') {
        //                            $q2->orWhere('category_slug', 'like', '%' . $word . '%');
        //                        }
        //                    }
        //                });
        //                $q3->orWhereHas("parentCategory",function ($q4) use ($words,$find){
        //                    $q4->Where(function ($q1) use($words,$find) {
        //                        foreach ($words as $word) {
        //                            if($find !== 0) {
        //                                $q1->whereRaw('FIND_IN_SET(?, REPLACE(category_slug, "-", ","))', $word);
        //                            }elseif($word != 'and') {
        //                                $q1->orWhere('category_slug', 'like', '%' . $word . '%');
        //                            }
        //                        }
        //                    });
        //                });
        //            });

        //            $q->orWhereHas("occasions", function ($q3) use ($words,$find) {
        //                $q3->WhereHas("occasionMainData",function ($q4) use ($words,$find){
        //                    $q4->Where(function ($q1) use($words,$find) {
        //                        foreach ($words as $word) {
        //                            if($find !== 0) {
        //                                $q1->whereRaw('FIND_IN_SET(?, REPLACE(occasion_slug, "-", ","))', $word);
        //                            }elseif($word != 'and') {
        //                                $q1->orWhere('occasion_slug', 'like', '%' . $word . '%');
        //                            }
        //                        }
        //                    });
        //                });
        //            });
        //
        //            $q->orWhereHas("classification", function ($q3) use ($words,$find) {
        //                $q3->Where(function ($q1) use($words,$find) {
        //                    foreach ($words as $word) {
        //                        if($find !== 0) {
        //                            $q1->whereRaw('FIND_IN_SET(?, REPLACE(class_slug, "-", ","))', $word);
        //                        }elseif($word != 'and') {
        //                            $q1->orWhere('class_slug', 'like', '%' . $word . '%');
        //                        }
        //                    }
        //                });
        //            });
        //
        //            $q->orWhereHas("interests", function ($q3) use ($words,$find) {
        //                $q3->WhereHas("interestMainData",function ($q4) use ($words,$find){
        //                    $q4->Where(function ($q1) use($words,$find) {
        //                        foreach ($words as $word) {
        //                            if($find !== 0) {
        //                                $q1->whereRaw('FIND_IN_SET(?, REPLACE(interest_slug, "-", ","))', $word);
        //                            }elseif($word != 'and') {
        //                                $q1->orWhere('interest_slug', 'like', '%' . $word . '%');
        //                            }
        //                        }
        //                    });
        //                });
        //            });


        //        });

        //        if($findInAttribute !== 0 && $find == 0 ){
        //
        //            $items->WhereHas("valuesData", function ($q3) use ($words) {
        //                $q3->WhereHas("valueMainData",function ($q4) use ($words){
        //                    $q4->where(function ($q1) use($words) {
        //                        foreach ($words as $key => $word){
        //                            if($key == 0){
        //                                $q1->Where('attribute_value', 'like', '%' . $word . '%');
        //
        //                            }else{
        //                                $q1->orWhere('attribute_value', 'like', '%' . $word . '%');
        //                            }
        //                        }
        //                    });
        //                });
        //            });
        //
        //        }elseif($find == 0){
        //            $items->orWhereHas("valuesData", function ($q3) use ($words) {
        //                $q3->WhereHas("valueMainData",function ($q4) use ($words){
        //                    $q4->where(function ($q1) use($words) {
        //                        foreach ($words as $key => $word){
        //                            if($key == 0){
        //                                $q1->Where('attribute_value', 'like', '%' . $word . '%');
        //                            }else{
        //                                $q1->orWhere('attribute_value', 'like', '%' . $word . '%');
        //                            }
        //                        }
        //                    });
        //                });
        //            });
        //        }
        //        $items->orWhere('description', '=',$text);
        //        $items->orWhere('name', '=', $text);


        if ($count > 0) {
            return $find->paginate($count);
        } else {
            return $find->count();
        }
    }

    //        $items = CatalogItem::where('name', 'LIKE', "%$text%")
    //        ->whereNull("parent_item_id")
    //        ->orWhere("parent_item_id", 0);

    static function searchItemByClassification($q = 'a', $classification = false, $count = 15)
    {
        if ($classification) {
            $items = (new CatalogItem)->newQuery();
            $items->where('name', 'LIKE', "%$q%");
            $items->whereHas("merchant", function ($merQ) {
                $merQ->where('country_id', app('session')->get('def_country')->id);
            });
            $items->whereHas("classification", function ($merQ) use ($classification) {
                $merQ->where('class_slug', $classification);
            });
            $items->where(function ($q) {
                $q->whereNull("parent_item_id");
                $q->orWhere("parent_item_id", 0);
            });

            return $items->paginate($count);
        }
        return false;
    }

    static function getItemIdsFromInterest($interests)
    {
        // $return = [];
        // foreach ($interests as $interest) {
        //     $itemsIDs = $interest->items;
        //     foreach ($itemsIDs as $id) {
        //         if (!in_array($id, $return)) {
        //             $return[] = $id;
        //         }
        //     }
        // }
        // return $return;
    }
}
