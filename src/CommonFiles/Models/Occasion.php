<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Occasion extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);

        static::addGlobalScope('country', function (\Illuminate\Database\Eloquent\Builder $builder) {

            $builder->whereHas('countryRelation', function ($countryQ) {
                $countryQ->where('country_id', app('session')->get('def_country')->id);
            });
        });
    }

    protected $table = 'occasion_types';

    public function scopeItems($query) {
        return $query->whereHas('occasionItemsData', function ($q){
           $q->whereHas('itemsMainData', function($query) {
            $query->where('is_published', '1');
        }); 
        });
    }

    public function countryRelation() {
        return $this->hasMany('OlaHub\UserPortal\Models\ManyToMany\occasionCountries', 'occasion_type_id');
    }

    public function occasionItemsData() {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemOccasions', 'occasion_id');
    }

    static function getBannerBySlug($slug) {
        $occasion = Occasion::where('occasion_slug', $slug)->first();
        if ($occasion && $occasion->banner_ref) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($occasion->banner_ref);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'shop_banner');
        }
    }

    static function getBannerByIDS($ids) {
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
    
    static function getStoreForAdsBySlug($slug) {
        $occassions = Occasion::where('occasion_slug', $slug)->first();
        $return = [
            'storeName' => NULL,
            'storeLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
        ];
        if ($occassions) {
            $user = app('session')->get('tempID') ? \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first() : false;
            $return = [
                'storeName' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occassions, 'name'),
                'storeLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($occassions->logo_ref),
                'followed' => $user && isset($user->followed_occassions) && is_array($user->followed_occassions) && in_array($occassions->id, $user->followed_occassions) ? true : false,
            ];
        }

        return $return;
    }

}
