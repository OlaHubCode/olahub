<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Interests extends Model
{
    protected $table = 'lkp_interests';

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        static::addGlobalScope('interestsCountry', function (\Illuminate\Database\Eloquent\Builder $builder) {
            // $builder->whereIn('countries', [(int) app('session')->get('def_country')->id]);
        });
    }


    public function scopeItems($query)
    {
        return $query->whereHas('itemsRelation', function ($q) {
            $q->whereHas('itemsMainData', function ($query) {
                $query->where('is_published', '1');
            });
        });
    }

    public function itemsRelation()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemInterests', 'interest_id');
    }

    static function getBannerBySlug($slug)
    {
        $interest = Interests::where('interest_slug', $slug)->first();
        if ($interest && $interest->image_banner) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($interest->image_banner);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'shop_banner');
        }
    }

    static function getStoreForAdsBySlug($slug)
    {
        $interest = Interests::where('interest_slug', $slug)->first();
        $return = [
            'storeName' => NULL,
            'storeLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
        ];
        if ($interest) {
            $return = [
                'storeName' => isset($interest->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($interest, "name") : NULL,
                'storeLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($interest->image_ref),
            ];
        }

        return $return;
    }

    static function searchInterests($q = 'a', $count = 15)
    {
        return Interests::where('name', 'LIKE', "%$q%")->select("interest_id")->get();
    }
}
