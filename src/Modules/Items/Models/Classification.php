<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Classification extends Model {

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

    protected $table = 'lkp_catalog_items_classification';
    static $columnsMaping = [
        'itemName' => [
            'column' => 'name',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:200'
        ],
        'itemID' => [
            'column' => 'id',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:200'
        ],
        'userPhoneNumber' => [
            'column' => 'mobile_no',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:200'
        ],
        'userEmail' => [
            'column' => 'email',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:200'
        ],
        'userPassword' => [
            'column' => 'password',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:200'
        ],
        'userCountry' => [
            'column' => 'country_id',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:200'
        ],
    ];

    public function itemsMainData() {
        return $this->hasMany('OlaHub\UserPortal\Models\CatalogItem', 'clasification_id');
    }

    static function getBannerBySlug($slug) {
        $class = Classification::where('class_slug', $slug)->first();
        if ($class && $class->banner_ref) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($class->banner_ref);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'shop_banner');
        }
    }

    static function getBannerByIDS($ids) {
        $classes = Classification::whereIn('id', $ids)->whereNotNull('banner_ref')->get();
        $return = [];
        if ($classes->count() > 1) {
            foreach ($classes as $class) {
                if ($class->banner_ref) {
                    $return[] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($class->banner_ref);
                }
            }
        } else {
            $return[] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'banner');
        }

        return $return;
    }

    static function getStoreForAdsBySlug($slug) {
        $classes = Classification::where('class_slug', $slug)->first();
        $return = [
            'storeName' => NULL,
            'storeLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
        ];
        if ($classes) {
            $return = [
                'storeName' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($classes, 'name'),
                'storeLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($classes->banner_ref),
            ];
        }

        return $return;
    }

    static function searchClassifications($q = 'a') {
        $classifications = Classification::whereHas("itemsMainData", function($itemQ) use($q) {
                    $itemQ->where('name', 'LIKE', "%$q%");
                    $itemQ->whereHas('merchant', function($merQ) {
                        $merQ->where('country_id', 5);
                    });
                    $itemQ->where(function($itemQW) {
                        $itemQW->whereNull("parent_item_id");
                        $itemQW->orWhere("parent_item_id", 0);
                    });
                })->groupBy('id');
        return $classifications->get();
    }

}
