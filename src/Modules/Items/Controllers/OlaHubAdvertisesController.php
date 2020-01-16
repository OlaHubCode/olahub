<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class OlaHubAdvertisesController extends BaseController {

    protected $requestData;
    protected $requestFilter;
    protected $mainModule;
    protected $return;
    protected $userAgent;

    public function __construct(Request $request) {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function getAdsData() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getAdsData"]);
       
        $this->return['status'] = true;
        $this->return['code'] = 200;
        $this->return['mainBanner'] = [];
        $this->checkSlugs();
        if(count($this->return['mainBanner']) < 1){
            $this->checkIDs();
        }
        if(count($this->return['mainBanner']) < 1){
            $this->return['mainBanner'][] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'shop_banner');
        }
        $log->setLogSessionData(['response' => $this->return]);
        $log->saveLogSessionData();
        return response($this->return, 200);
    }

    /*
     * Helper functions
     */

    private function checkSlugs() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "checkSlugs"]);
       
        if((array_key_exists('brandSlug', $this->requestFilter) && strlen($this->requestFilter['brandSlug']) > 0)){
            $this->mainModule = 'brand';
            $this->return = \OlaHub\UserPortal\Models\Brand::getBannerBySlug($this->requestFilter['brandSlug']);
        }elseif((array_key_exists('merchantSlug', $this->requestFilter) && strlen($this->requestFilter['merchantSlug']) > 0)){
            $this->mainModule = 'merchant';
            $this->return['mainBanner'][] = \OlaHub\UserPortal\Models\Merchant::getBannerBySlug($this->requestFilter['merchantSlug']);
            $this->return['storeData'] = \OlaHub\UserPortal\Models\Merchant::getStoreForAdsBySlug($this->requestFilter['merchantSlug']);
        }elseif((array_key_exists('categorySlug', $this->requestFilter) && strlen($this->requestFilter['categorySlug']) > 0)){
            $this->mainModule = 'category';
            $this->return['mainBanner'][] = \OlaHub\UserPortal\Models\ItemCategory::getBannerBySlug($this->requestFilter['categorySlug']);
            $this->return['storeData'] = \OlaHub\UserPortal\Models\ItemCategory::getStoreForAdsBySlug($this->requestFilter['categorySlug']);
        }elseif((array_key_exists('classificationSlug', $this->requestFilter) && strlen($this->requestFilter['classificationSlug']) > 0)){
            $this->mainModule = 'calssification';
            $this->return['mainBanner'][] = \OlaHub\UserPortal\Models\Classification::getBannerBySlug($this->requestFilter['classificationSlug']);
            $this->return['storeData'] = \OlaHub\UserPortal\Models\Classification::getStoreForAdsBySlug($this->requestFilter['classificationSlug']);
        }elseif((array_key_exists('occasionSlug', $this->requestFilter) && strlen($this->requestFilter['occasionSlug']) > 0)){
            $this->mainModule = 'occasion';
            $this->return['mainBanner'][] = \OlaHub\UserPortal\Models\Occasion::getBannerBySlug($this->requestFilter['occasionSlug']);
            $this->return['storeData'] = \OlaHub\UserPortal\Models\Occasion::getStoreForAdsBySlug($this->requestFilter['occasionSlug']);
        }elseif((array_key_exists('interestSlug', $this->requestFilter) && strlen($this->requestFilter['interestSlug']) > 0)){
            $this->mainModule = 'occasion';
            $this->return['mainBanner'][] = \OlaHub\UserPortal\Models\Interests::getBannerBySlug($this->requestFilter['interestSlug']);
            $this->return['storeData'] = \OlaHub\UserPortal\Models\Interests::getStoreForAdsBySlug($this->requestFilter['interestSlug']);
        }
    }

    private function checkIDs() {
         $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "checkIDs"]);
       
        if((array_key_exists('brands', $this->requestFilter) && count($this->requestFilter['brands']) > 0) && (array_key_exists('categories', $this->requestFilter) && count($this->requestFilter['categories']) > 0)){
            $this->mainModule = 'brand';
            $this->return['mainBanner'] = \OlaHub\UserPortal\Models\Brand::getBannerByIDS($this->requestFilter['brands']);
        }elseif((array_key_exists('categories', $this->requestFilter) && count($this->requestFilter['categories']) > 0)){
            $this->mainModule = 'category';
            $this->return['mainBanner'] = \OlaHub\UserPortal\Models\ItemCategory::getBannerByIDS($this->requestFilter['categories']);
        }elseif((array_key_exists('classifications', $this->requestFilter) && count($this->requestFilter['classifications']) > 0)){
            $this->mainModule = 'calssification';
            $this->return['mainBanner'] = \OlaHub\UserPortal\Models\Classification::getBannerByIDS($this->requestFilter['classifications']);
        }elseif((array_key_exists('occasions', $this->requestFilter) && count($this->requestFilter['occasions']) > 0)){
            $this->mainModule = 'occasion';
            $this->return['mainBanner'] = \OlaHub\UserPortal\Models\Occasion::getBannerByIDS($this->requestFilter['occasions']);
        }
    }

}
