<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use OlaHub\UserPortal\Models\CatalogItem;

class OlaHubLandingPageController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    protected $userAgent;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function getItemsSitemap()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getSitemapData"]);

        // occasions
        $occasions = \OlaHub\UserPortal\Models\Occasion::items()
            ->orderBy("order_occasion", "ASC")
            ->orderBy("name", "ASC")
            ->limit(15)
            ->get();
        $occasions = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($occasions, '\OlaHub\UserPortal\ResponseHandlers\OccasionsHomeResponseHandler');
        $return['occasions'] = $occasions['data'];

        // interests
        $interests = \OlaHub\UserPortal\Models\Interests::has('itemsRelation')->orderBy('name', 'ASC')->limit(15)->get();
        $interests = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($interests, '\OlaHub\UserPortal\ResponseHandlers\InterestsHomeResponseHandler');
        $return['interests'] = $interests['data'];

        // // offers
        $itemModel = (new \OlaHub\UserPortal\Models\CatalogItem)->newQuery();
        // $itemModel->selectRaw('*, ((discounted_price / price) * 100) as discount_perc');

        $itemModel->where(function ($query) {
            $query->selectRaw('*, ((discounted_price / price) * 100) as discount_perc')
                ->whereNotNull('discounted_price_start_date')
                ->whereNotNull('discounted_price_end_date')
                ->where('discounted_price_start_date', '<=', date('Y-m-d') . " 00:00:01")
                ->where('discounted_price_end_date', '>=', date('Y-m-d') . " 23:59:59")
                ->where('discounted_price_end_date', '>=', date('Y-m-d') . " 23:59:59")
                ->orderByRaw("RAND()")
                ->take(15);
        });
        $items = $itemModel->get();
        $offers = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($items, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler');
        $return['offers'] = $offers['data'];

        // trending
        $itemModel = (new \OlaHub\UserPortal\Models\CatalogItem)->newQuery();
        $itemModel->where(function ($query) {
            $query->whereNull('parent_item_id');
            $query->orWhere('parent_item_id', '0');
        });
        $itemModel->orderBy('total_views', 'DESC');
        $itemModel->orderBy('name', 'ASC');
        $itemModel->take(15);
        $items = $itemModel->get();
        $trending = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($items, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler');
        $return['trending'] = $trending['data'];

        // brands // merchant_stors
        $brandsModel = (new \OlaHub\UserPortal\Models\Brand)->newQuery();
        $brandsModel->whereHas('itemsMainData', function ($query) {
            $query->where('is_published', '1');
        })->take(14);
        $brands = $brandsModel->get();
        $brands = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($brands, '\OlaHub\UserPortal\ResponseHandlers\BrandsResponseHandler');
        $return['brands'] = $brands['data'];

        // men classification
        $classQuery = (new CatalogItem)->newQuery();
        $menClass = $classQuery->whereHas('classification', function ($q) {
            $q->where('class_slug', 'men');
            $q->whereNull('catalog_items.parent_item_id');
            $q->orWhere('catalog_items.parent_item_id', '0');
        })->orderBy('created_at', 'DESC')->groupBy('catalog_items.id');
        $menItems = $menClass->paginate(20);
        $menItems = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($menItems, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler');
        $classifications['men'] = $menItems['data'];

        // women classification
        $classQuery = (new CatalogItem)->newQuery();
        $womenClass = $classQuery->whereHas('classification', function ($q) {
            $q->where('class_slug', 'women');
            $q->whereNull('catalog_items.parent_item_id');
            $q->orWhere('catalog_items.parent_item_id', '0');
        })->orderBy('created_at', 'DESC')->groupBy('catalog_items.id');
        $womenItems = $womenClass->paginate(20);
        $womenItems = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($womenItems, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler');
        $classifications['women'] = $womenItems['data'];

        $return['classifications'] = $classifications;

        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }
    public function getTrendingData()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getTrendingData"]);

        $itemModel = (new \OlaHub\UserPortal\Models\CatalogItem)->newQuery();
        $itemModel->whereHas('quantityData', function ($q) {
            $q->where('quantity', '>', 0);
        })->where(function ($query) {
            $query->whereNull('parent_item_id');
            $query->orWhere('parent_item_id', '0');
        });
        $itemModel->orderBy('total_views', 'DESC');
        $itemModel->orderBy('name', 'ASC');
        $itemModel->take(15);
        $items = $itemModel->get();
        if ($items->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($items, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler');
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getMostOfferData()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getMostOfferData"]);

        $itemModel = (new \OlaHub\UserPortal\Models\CatalogItem)->newQuery();
        $itemModel->selectRaw('*, ((discounted_price / price) * 100) as discount_perc');

        $itemModel->whereHas('quantityData', function ($q) {
            $q->where('quantity', '>', 0);
        });
        $itemModel->where(function ($query) {
            $query->whereNotNull('discounted_price_start_date');
            $query->whereNotNull('discounted_price_end_date');
            $query->where('discounted_price_start_date', '<=', date('Y-m-d') . " 00:00:01");
            $query->where('discounted_price_end_date', '>=', date('Y-m-d') . " 23:59:59");
        });
        $itemModel->orderByRaw("RAND()");
        $itemModel->take(15);
        $items = $itemModel->get();
        if ($items->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($items, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler');
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getOccasionsData()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getOccasionsData"]);

        $occasions1 = \OlaHub\UserPortal\Models\Occasion::items()
            ->whereNotNull("order_occasion")
            ->orderBy("order_occasion", "ASC")
            ->get();
        $occasions2 = \OlaHub\UserPortal\Models\Occasion::items()
            ->whereNull("order_occasion")
            ->inRandomOrder()
            ->get();
        if ($occasions1->count() < 1 && $occasions2->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $occasions1 = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($occasions1, '\OlaHub\UserPortal\ResponseHandlers\OccasionsHomeResponseHandler');
        $occasions2 = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($occasions2, '\OlaHub\UserPortal\ResponseHandlers\OccasionsHomeResponseHandler');
        $return['data'] = array_merge($occasions1['data'], $occasions2['data']);
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getInterestsData()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getInterestsData"]);

        $interests1 = \OlaHub\UserPortal\Models\Interests::has('itemsRelation')
            ->whereNotNull("order_occasion")
            ->orderBy('order_occasion', 'ASC')
            ->get();

        $interests2 = \OlaHub\UserPortal\Models\Interests::has('itemsRelation')
            ->whereNull("order_occasion")
            ->inRandomOrder()
            ->get();

        if ($interests1->count() < 1 && $interests2->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $interests1 = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($interests1, '\OlaHub\UserPortal\ResponseHandlers\InterestsHomeResponseHandler');
        $interests2 = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($interests2, '\OlaHub\UserPortal\ResponseHandlers\InterestsHomeResponseHandler');
        $return['data'] = array_merge($interests1['data'], $interests2['data']);
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getBrandsData()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getBrandsData"]);

        $brandsModel = (new \OlaHub\UserPortal\Models\Brand)->newQuery();
        $brandsModel->whereHas('itemsMainData', function ($query) {
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

    private function getClasses($type)
    {
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
        $classesMainModel->whereHas('itemsMainData', function ($query) {
            $query->where('is_published', '1');
        });
        return $classesMainModel->get();
    }
}
