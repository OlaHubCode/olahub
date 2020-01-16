<?php

namespace OlaHub\UserPortal\Models;

class ItemCategory extends \Illuminate\Database\Eloquent\Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }

    protected $table = 'catalog_item_categories';
    protected $columnsMaping = [
        'catName' => [
            'column' => 'name',
            'type' => 'multiLang',
            'relation' => false,
            'validation' => 'required|max:4000'
        ],
        'catParent' => [
            'column' => 'parent_id',
            'type' => 'numNull',
            'manyToMany' => false,
            'validation' => '',
            'filterValidation' => 'integer',
        ],
    ];

    public function countryRelation() {
        return $this->hasMany('OlaHub\UserPortal\Models\ManyToMany\ItemCountriesCategory', 'category_id');
    }

    public function childsData() {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemCategory', 'parent_id');
    }

    public function parentCategory() {
        return $this->belongsTo('OlaHub\UserPortal\Models\ItemCategory', 'parent_id');
    }

    public function itemsMainData() {
        return $this->hasMany('OlaHub\UserPortal\Models\CatalogItem', 'category_id');
    }

    static function setReturnResponse($categories, $itemsIDs = false) {
        $return['data'] = [];
        foreach ($categories as $category) {
            $catData = [
                "classID" => isset($category->id) ? $category->id : 0,
                "classSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($category, 'category_slug', \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($category, 'name')),
                "className" => isset($category->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($category, 'name') : NULL,
            ];
            
            $catData['childsData'] = [];
            if ($itemsIDs) {
                $childs = $category->childsData()->whereHas('itemsMainData', function($q) use($itemsIDs) {
                            $q->whereIn('id', $itemsIDs);
                        })->get();
            } else {
                $childs = $category->childsData()->has('itemsMainData')->whereHas('countryRelation', function($q) {
                            $q->where('country_id', app('session')->get('def_country')->id);
                        })->get();
            }
            foreach ($childs as $child) {
                $catData['childsData'][] = [
                    "classID" => isset($child->id) ? $child->id : 0,
                    "classSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($child, 'category_slug', \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($child, 'name')),
                    "className" => isset($category->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($child, 'name') : NULL,
                ];
            }
            $return['data'][] = $catData;
        }
        return (array) $return;
    }

    static function getBannerBySlug($slug) {
        $category = ItemCategory::where('category_slug', $slug)->first();
        if ($category && $category->banner_ref) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($category->banner_ref);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'shop_banner');
        }
    }

    static function getBannerByIDS($ids) {
        $categories = ItemCategory::whereIn('id', $ids)->whereNotNull('banner_ref')->get();
        $return = [];
        if ($categories->count() > 1) {
            foreach ($categories as $category) {
                if ($category->banner_ref) {
                    $return[] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($category->banner_ref);
                }
            }
        } else {
            $return[] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'banner');
        }

        return $return;
    }

    static function getIDsByID($ids) {
        if (is_array($ids)) {
            $return = $ids;
        } else {
            $return[] = $ids;
        }
        $childs = ItemCategory::whereIn('parent_id', $ids)->pluck('id')->toArray();
        foreach ($childs as $child) {
            $return[] = $child;
        }
        return $return;
    }

    static function getIDsBySlug($value) {
        $return = [];
        $category = ItemCategory::where('category_slug', $value)->first();
        if ($category) {
            $childs = ItemCategory::where('parent_id', $category->id)->pluck('id')->toArray();
            $return['main']['column'] = 'category_id';
            $return['main']['values'] = $childs;
            $return['main']['values'][] = $category->id;
        } else {
            $return = $value;
        }

        return $return;
    }
    
    static function getStoreForAdsBySlug($slug) {
        $category = ItemCategory::where('category_slug', $slug)->first();
        $return = [
            'storeName' => NULL,
            'storeLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
        ];
        if ($category) {
            $return = [
                'storeName' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($category, 'name'),
                'storeLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($category->banner_ref),
            ];
        }

        return $return;
    }

}
