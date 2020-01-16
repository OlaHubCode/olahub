<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);

        static::addGlobalScope('country', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->whereHas('itemsMainData', function ($brandMerQ) {
                $brandMerQ->where('is_published', 1);
            });

            $builder->whereHas('merchant', function ($merQ) {
                $merQ->where('country_id', app('session')->get('def_country')->id);
            });
        });
    }

    protected $table = 'merchant_stors';

    public function itemsMainData() {
        return $this->hasMany('OlaHub\UserPortal\Models\CatalogItem', 'store_id');
    }

    public function merchant() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Merchant', 'merchant_id');
    }

    public function brandMerRelation() {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemBrandMer', 'brand_id');
    }

    static function getBannerBySlug($slug) {
        $return['mainBanner'] = [\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'shop_banner')];
        $return['storeData']['storeLogo'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        $brand = Brand::where('store_slug', $slug)->first();
        if ($brand) {
            $user = app('session')->get('tempID') ? \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first() : false;
            $return['mainBanner'] = [\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brand->banner_ref, 'shop_banner')];
            $return['storeName'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($brand, 'name');
            $return['storeData']['storeLogo'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brand->image_ref);
            $return['followed'] = $user && isset($user->followed_brands) && is_array($user->followed_brands) && in_array($brand->id, $user->followed_brands) ? true : false;
        }
        return $return;
    }

    static function getBannerByIDS($ids) {
        $brands = Brand::whereIn('id', $ids)->whereNotNull('banner_ref')->get();
        $return = [];
        if ($brands->count() > 1) {
            foreach ($brands as $brand) {
                if ($brand->banner_ref) {
                    $return[] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brand->banner_ref);
                }
            }
        } else {
            $return[] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'banner');
        }

        return $return;
    }

    static function searchBrands($q = 'a', $count = 15) {
        $brands = Brand::where('name', 'LIKE', "%$q%")
                ->whereHas("itemsMainData", function($itemQ) use($q) {
                    //$itemQ->where("name", "like", "%$q%");
                    $itemQ->where("is_published", "1");
            $itemQ->whereHas('merchant', function($merQ) {
                $merQ->where('country_id', 5);
            });
        });
        if ($count > 0) {
            return $brands->paginate($count);
        }else{
            return $brands->count();
        }
    }

}
