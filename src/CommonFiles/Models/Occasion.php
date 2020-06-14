<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Occasion extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        static::addGlobalScope('country', function (\Illuminate\Database\Eloquent\Builder $builder) {

            $builder->whereHas('countryRelation', function ($countryQ) {
                $countryQ->where('country_id', app('session')->get('def_country')->id);
            });
        });
    }

    protected $table = 'occasion_types';

    public function scopeItems($query)
    {
        return $query->whereHas('occasionItemsData', function ($q) {
            $q->whereHas('itemsMainData', function ($query) {
                $query->where('is_published', '1');
            });
        });
    }

    public function countryRelation()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\ManyToMany\occasionCountries', 'occasion_type_id');
    }

    public function occasionItemsData()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemOccasions', 'occasion_id');
    }

    static function getBannerBySlug($slug)
    {
        // $occasion = Occasion::where('occasion_slug', $slug)->first();
        // if ($occasion && $occasion->banner_ref) {
        //     return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($occasion->banner_ref);
        // } else {
        //     return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'shop_banner');
        // }

        
        $return['mainBanner'] = [\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'shop_banner')];
        $return['storeData']['storeLogo'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        $occasion = Occasion::where('occasion_slug', $slug)->first();
        if ($occasion) {
            $follow = \OlaHub\UserPortal\Models\Following::where("user_id", app('session')->get('tempID'))->where('target_id', $occasion->id)
                ->where('type', 4)->first();
            $return['id'] = $occasion->id;
            $return['mainBanner'] = [\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($occasion->banner_ref, 'shop_banner')];
            $return['storeName'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occasion, 'name');
            $return['storeData']['storeLogo'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($occasion->logo_ref);
            $return['followed'] = isset($follow) ? true : false;
        }
        return $return;
    }

    static function getBannerByIDS($ids)
    {
        $occassions = Occasion::whereIn('id', $ids)->whereNotNull('banner_ref')->get();
        $return = [];
        if ($occassions->count() > 1) {
            foreach ($occassions as $occassion) {
                if ($occassion->banner_ref) {
                    $return[] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($occassion->banner_ref);
                }
            }
        } else {
            $return[] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'banner');
        }

        return $return;
    }

    static function getStoreForAdsBySlug($slug)
    {
        $occassions = Occasion::where('occasion_slug', $slug)->first();
        $follow = \OlaHub\UserPortal\Models\Following::where("user_id", app('session')->get('tempID'))->where('target_id', $occassions->id)
            ->where('type', 4)->first();
        $return = [
            'storeName' => NULL,
            'storeLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
        ];
        if ($occassions) {
            $return = [
                'id' => $occassions->id,
                'storeName' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occassions, 'name'),
                'storeLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($occassions->logo_ref),
            ];
            $return['followed'] = isset($follow) ? true : false;
        }

        return $return;
    }
}
