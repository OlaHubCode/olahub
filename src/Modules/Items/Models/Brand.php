<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{

    public function __construct(array $attributes = array())
    {
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

    public function itemsMainData()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\CatalogItem', 'store_id');
    }

    public function merchant()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\Merchant', 'merchant_id');
    }

    public function brandMerRelation()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemBrandMer', 'brand_id');
    }

    static function getBannerBySlug($slug)
    {
        $return['mainBanner'] = [\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'shop_banner')];
        $return['storeData']['storeLogo'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        $brand = Brand::where('store_slug', $slug)->first();
        if ($brand) {
            $follow = \OlaHub\UserPortal\Models\Following::where("user_id", app('session')->get('tempID'))->where('target_id', $brand->id)
                ->where('type', 1)->first();
            $return['id'] = $brand->id;
            $return['mainBanner'] = [\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brand->banner_ref, 'shop_banner')];
            $return['storeName'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($brand, 'name');
            $return['storeData']['storeLogo'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brand->image_ref);
            $return['followed'] = isset($follow) ? true : false;
        }
        return $return;
    }

    static function getBannerByIDS($ids)
    {
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

    static function searchBrands($text = 'a', $count = 15)
    {
        $words = explode(" ", $text);
        $items = Brand::where(function ($q) use ($words) {

            $q->where(function ($q1) use ($words) {
                foreach ($words as $word) {
                    $q1->whereRaw('FIND_IN_SET(?, REPLACE(name, " ", ","))', $word);
                }
            });
            $q->orWhere(function ($q3) use ($words) {
                foreach ($words as $word) {
                    $length = strlen($word);
                    if ($length >= 3) {
                        $firstWords = substr($word, 0, 3);
                        $q3->whereRaw('LOWER(`name`) LIKE ? ', '%' . $firstWords . '%');

                        if ($length >= 6) {
                            $lastWords = substr($word, -3);
                            $q3->WhereRaw('LOWER(`name`) LIKE ? ', '%' . $lastWords . '%');
                        }
                    } else if ($length == 2) {
                        $q3->whereRaw('LOWER(`name`) LIKE ? ', '%' . $word . '%');
                    }
                }
            });
        });

        $items->orWhere('name', '=', $text);


        if ($count > 0) {
            return $items->paginate($count);
        } else {
            return $items->count();
        }
    }
}
