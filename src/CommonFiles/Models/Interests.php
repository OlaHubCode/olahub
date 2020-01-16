<?php

namespace OlaHub\UserPortal\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Interests extends Eloquent {

    protected $connection = 'mongo';
    protected $collection = 'interests';
    
    
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);

        static::addGlobalScope('interestsCountry', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->whereIn('countries', [(int)app('session')->get('def_country')->id]);
        });
    }
    
    

    public function itemsRelation() {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem', 'items', 'id');
    }
    
    static function getBannerBySlug($slug) {
        $interest = Interests::where('interest_slug', $slug)->first();
//        if ($interest && $interest->image) {
//            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($interest->image);
//        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'shop_banner');
//        }
    }
    
    static function getStoreForAdsBySlug($slug) {
        $interest = Interests::where('interest_slug', $slug)->first();
        $return = [
            'storeName' => NULL,
            'storeLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
        ];
        if ($interest) {
            $user = app('session')->get('tempID') ? \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first() : false;
            $return = [
                'storeName' => isset($interest->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($interest, "name") : NULL,
                'storeLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($interest->image),
                'followed' => $user && isset($user->followed_interests) && is_array($user->followed_interests) && in_array($interest->id, $user->followed_interests) ? true : false,
            ];
        }

        return $return;
    }
    
    static function searchInterests($q = 'a', $count = 15) {
        $interests = Interests::where('name', 'LIKE', "%$q%")
                ->whereIn('countries', [app('session')->get('def_country')->id])->select("interest_id")->get();
        
        return $interests;
        
    }

}
