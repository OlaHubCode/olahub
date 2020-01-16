<?php

/**
 * MerGeneralInfos model 
 * To connect with database and make all queries  
 * all functions return with eloqouent object or array of objects
 * 
 * @author Mohamed EL-Absy <mohamed.elabsy@yahoo.com>
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0 
 */

namespace OlaHub\UserPortal\Models;

class Merchant extends \Illuminate\Database\Eloquent\Model {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);

        static::addGlobalScope('country', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('country_id', app('session')->get('def_country')->id);
        });
    }

    protected $table = 'merchants';
    protected $columnsMaping = [
        'merGeneralInfoCountry' => [
            'column' => 'country_id',
            'type' => 'num',
            'relation' => false,
            'validation' => 'required|numeric|exists:countries,id'
        ],
        'merGeneralInfoCompanyName' => [
            'column' => 'company_legal_name',
            'type' => 'string',
            'manyToMany' => false,
            'validation' => 'required|max:200',
        ],
        'merGeneralInfoCompanyWebsite' => [
            'column' => 'company_website',
            'type' => 'string',
            'manyToMany' => false,
            'validation' => 'max:200',
        ],
    ];

    public function storeRelation() {
        return $this->hasMany('OlaHub\UserPortal\Models\MerStore', 'merchant_id');
    }

    public function itemRelation() {
        return $this->hasMany('OlaHub\UserPortal\Models\CatalogItem', 'merchant_id');
    }

    public function merCatsRelation() {
        return $this->hasMany('OlaHub\UserPortal\Models\MerchantCategory', 'merchant_id');
    }

    public function userAccount() {
        return $this->hasOne('OlaHub\UserPortal\Models\UserModel', 'for_merchant');
    }

    static function getBannerBySlug($slug) {
        $merchant = Merchant::where('merchant_slug', $slug)->first();
        if ($merchant && $merchant->company_banner_image_ref && $merchant->company_banner_image_ref != NULL) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($merchant->company_banner_image_ref);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'shop_banner');
        }
    }

    static function getStoreForAdsBySlug($slug) {
        $merchant = Merchant::where('merchant_slug', $slug)->first();
        $return = [
            'storeName' => NULL,
            'storeLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
            'storeLink' => NULL,
            'storePhone' => NULL,
        ];
        if ($merchant) {
            $return = [
                'storeName' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($merchant, 'company_legal_name'),
                'storeLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($merchant->company_banner_image_ref),
                'storeLink' => isset($merchant->company_website) ? $merchant->company_website : NULL,
                'storePhone' => isset($merchant->company_phone_no) ? $merchant->company_phone_no : NULL,
            ];
        }

        return $return;
    }

    static function searchStores($q = 'a', $count = 15) {
        $stores = Merchant::where('company_legal_name', 'LIKE', "%$q%")
                ->has("itemRelation")
                ->take($count);
        return $stores->get();
    }

}
