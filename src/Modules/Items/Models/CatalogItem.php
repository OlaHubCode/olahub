<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogItem extends Model
{
    // use SoftDeletes;
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
    static function searchItem($text = 'a', $count = 15, $withRelated = false)
    {
        $text_length = strlen($text) - substr_count($text, ' ');
        $array = ['a', 'and', 'around', 'every', 'for', 'from', 'in', 'is', 'it', 'not', 'on', 'one', 'the', 'to', 'under'];
        $text = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::replaceSpecChars($text);
        $words = explode(" ", $text);
        $words = array_filter($words, function ($word) use ($array) {
            if (!empty($word) && !in_array($word, $array)) {
                return $word;
            }
        });
        $occQuery = [];
        foreach ($words as $word)
            // array_push($occQuery, "replace(LOWER(JSON_EXTRACT(name, '$.en')), '\'', '') REGEXP '$word'");
            array_push($occQuery, "replace(replace(LOWER(JSON_EXTRACT(name, '$.en')), '\'', ''), '\"', '') REGEXP '$word'");

        // each items
        $itemQuery = [];
        foreach ($words as $word)
            array_push($itemQuery, "replace(replace(LOWER(name), '\'', ''), '\"', '') REGEXP '[[:<:]]" . $word . "[[:>:]]'");
        $itemQuery = join(' and ', $itemQuery);
        // end each 

        $whereQuery = join(' and ', $occQuery);
        $find = CatalogItem::where(function ($query) {
            $query->whereNull('parent_item_id');
            $query->orWhere('parent_item_id', '0');
        })->where(function ($q) use ($whereQuery) {
            //occasions
            $q->whereHas("occasionSync", function ($q1) use ($whereQuery) {
                $q1->whereRaw($whereQuery);
            });
            // interests
            $q->orWhereHas("interestSync", function ($q1) use ($whereQuery) {
                $q1->whereRaw($whereQuery);
            });
            //categories
            $q->orWhereHas("category", function ($q1) use ($whereQuery) {
                $q1->whereRaw($whereQuery);
                $q1->orWhereHas("parentCategory", function ($q2) use ($whereQuery) {
                    $q2->whereRaw($whereQuery);
                });
            });
            //classification
            $q->orWhereHas("classification", function ($q1) use ($whereQuery) {
                $q1->whereRaw($whereQuery);
            });
        })->orWhere(function ($q1) use ($itemQuery) {
            $q1->whereRaw($itemQuery);
        });
        $related = false;
        $newWords = $words;
        foreach ($newWords as $key => $word)
            if (strlen($word) < 2)
                unset($newWords[$key]);

        $whereQuery = "replace(replace(LOWER(JSON_EXTRACT(name, '$.en')), '\'', ''), '\"', '') REGEXP '" . join('|', $newWords) . "'";
        $whereQuery .= " and replace(LOWER(JSON_EXTRACT(name, '$.en')), '\'', '') <> replace(LOWER('\"$text\"'), '\'', '')";
        if ($withRelated) {
            $related = [];
            $occasions = Occasion::whereRaw($whereQuery)->whereHas('occasionItemsData')->get();
            $categories = ItemCategory::whereRaw($whereQuery)->whereHas("itemsMainData")->get();
            $interests = Interests::whereRaw($whereQuery)->whereHas("itemsRelation")->get();

            if ($occasions->count()) {
                foreach ($occasions as $occasion) {
                    $related[] = [
                        "name" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occasion, "name"),
                        "type" => "occasion"
                    ];
                }
            }
            if ($categories->count()) {
                foreach ($categories as $category) {
                    $related[] = [
                        "name" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($category, "name"),
                        "type" => "category"
                    ];
                }
            }
            if ($interests->count()) {
                foreach ($interests as $interest) {
                    $related[] = [
                        "name" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($interest, "name"),
                        "type" => "interest"
                    ];
                }
            }
        }
        // check the attributes values
        if ($find->count() == 0) {
            $tempWords = [];
            $attrQuery = [];

            foreach ($words as $word)
                array_push($attrQuery, "replace(replace(LOWER(attribute_value), '\'', ''), '\"', '') REGEXP '[[:<:]]" . $word . "[[:>:]]'");
            $arrQuery = join(' or ', $attrQuery);
            $findValue = AttrValue::whereRaw($arrQuery)->pluck('attribute_value')->toArray();

            foreach ($findValue as $kw) {
                foreach ($words as $word) {
                    if (strpos(strtolower($kw), strtolower($word)) !== false && !in_array($word, $tempWords))
                        $tempWords[] = $word;
                }
            }

            $find->orWhere(function ($q1) use ($whereQuery, $tempWords) {
                $q1->where(function ($query) {
                    $query->whereNull('parent_item_id');
                    $query->orWhere('parent_item_id', '0');
                });
                $q1->where(function ($qx) use ($tempWords) {
                    foreach ($tempWords as $word) {
                        $qx->whereHas("valuesData", function ($q2) use ($word) {
                            $q2->whereHas("valueMainData", function ($q3) use ($word) {
                                $q3->whereRaw("replace(replace(LOWER(attribute_value), '\'', ''), '\"', '') REGEXP '$word'");
                            });
                        });
                    }
                });
                $q1->where(function ($q) use ($whereQuery) {
                    $q->whereHas("occasionSync", function ($q2) use ($whereQuery) {
                        $q2->whereRaw($whereQuery);
                    });
                    $q->orWhereHas("interestSync", function ($q2) use ($whereQuery) {
                        $q2->whereRaw($whereQuery);
                    });
                    $q->orWhereHas("category", function ($q2) use ($whereQuery) {
                        $q2->whereRaw($whereQuery);
                        $q2->orWhereHas("parentCategory", function ($q2) use ($whereQuery) {
                            $q2->whereRaw($whereQuery);
                        });
                    });
                    $q->orWhereHas("classification", function ($q2) use ($whereQuery) {
                        $q2->whereRaw($whereQuery);
                    });
                });
            });
        }

        // check the item name
        if ($find->count() == 0) {
            // $itemQuery = [];
            // foreach ($words as $word)
            //     array_push($itemQuery, "replace(replace(LOWER(name), '\'', ''), '\"', '') REGEXP '[[:<:]]" . $word . "[[:>:]]'");
            // $itemQuery = join(' and ', $itemQuery);

            $find->orWhere(function ($q1) use ($text) {
                $q1->where(function ($query) {
                    $query->whereNull('parent_item_id');
                    $query->orWhere('parent_item_id', '0');
                });
                $q1->whereRaw("LOWER(name) LIKE '%" . strtolower($text) . "%'");
            });
        }

        $data = NULL;
        if ($count > 0) {
            $data = $find->paginate($count);
        } else {
            $data = $find->count();
        }
        return array(
            "data" => $data,
            "related" => $related
        );
    }

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
