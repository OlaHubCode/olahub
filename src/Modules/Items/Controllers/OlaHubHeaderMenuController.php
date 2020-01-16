<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class OlaHubHeaderMenuController extends BaseController {

    protected $requestData;
    protected $requestFilter;
    protected $userAgent;

    public function __construct(Request $request) {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function getCategoriesData() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getCategoriesData"]);
       
        $categorisModel = (new \OlaHub\UserPortal\Models\ItemCategory)->newQuery();
        $categorisModel->whereHas('countryRelation', function($q) {
            $q->where('country_id', app('session')->get('def_country')->id);
        });
        $categorisModel->has('itemsMainData')->whereNull('parent_id')->orWhere('parent_id', '0');
        $categorisModel->orWhereHas('childsData', function($childQ) {
            $childQ->has('itemsMainData');
            $childQ->whereHas('countryRelation', function($q) {
                $q->where('country_id', app('session')->get('def_country')->id);
            });
        });
        $categories = $categorisModel->get();

        if ($categories->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return = \OlaHub\UserPortal\Models\ItemCategory::setReturnResponse($categories);
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getClassificationsData() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getClassificationsData"]);
       
        $return['otherClaasses'] = [];
        $return['mainClaasses'] = [];
        $classesMain = $this->getClasses('1');
        if ($classesMain->count() >= 1) {
            $return['mainClaasses'] = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($classesMain, '\OlaHub\UserPortal\ResponseHandlers\ClassificationResponseHandler');
        }
//        unset($classesMain);
//
//        $classesOther = $this->getClasses('0');
//        if ($classesOther->count() >= 1) {
//            $return['otherClaasses'] = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($classesOther, '\OlaHub\UserPortal\ResponseHandlers\ClassificationResponseHandler');
//        }
//
//        unset($classesOther);
        $return['status'] = true;
        $return['code'] = 200;
         $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getBrandsData() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getBrandsData"]);
       
        $brandsModel = (new \OlaHub\UserPortal\Models\Brand)->newQuery();
        $brandsModel->whereHas('itemsMainData', function($query) {
            $query->where('is_published', '1');
        });
        $brands = $brandsModel->get();
        if ($brands->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($brands, '\OlaHub\UserPortal\ResponseHandlers\BrandsResponseHandler');
        unset($brands);
        $return['status'] = true;
        $return['code'] = 200;
         $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    /*
     * Helper functions
     */

    private function getClasses($type) {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getClasses"]);
       
        $classesMainModel = (new \OlaHub\UserPortal\Models\Classification)->newQuery();
//        if (count($this->requestFilter) > 0) {
//            foreach ($this->requestFilter as $input => $value) {
//                $classesMainModel->where(\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(\OlaHub\UserPortal\Models\Classification::$columnsMaping, $input), $value);
//            }
//            unset($value, $input);
//        }
        $classesMainModel->where('is_main', $type);
        $classesMainModel->whereHas('itemsMainData', function($query) {
            $query->where('is_published', '1');
        });
        return $classesMainModel->get();
    }

}
