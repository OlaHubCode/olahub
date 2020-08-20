<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\DesignerItems;
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
        $data = [];

        // if(app('session')->get('tempID')) {

        //     // $test = \OlaHub\UserPortal\Models\Following::with('category')->get();
        //     // var_dump($test);return " ";

        //     // Category items Start
        //     $followedCategory = \OlaHub\UserPortal\Models\Following::where("user_id", app('session')->get('tempID'))->where('type', 3)
        //         ->select('catalog_item_categories.id')
        //         ->join('catalog_item_categories', 'catalog_item_categories.parent_id', 'following.target_id')->get();
        //     $categoryIds = [];
        //     foreach ($followedCategory as $followedCategoryID) {
        //         $categoryIds[] = $followedCategoryID->id;
        //     }
        //     $cItems = \OlaHub\UserPortal\Models\CatalogItem::whereHas('quantityData', function ($q) {
        //         $q->where('quantity', '>', 0);
        //     })->where(function ($query) {
        //         $query->whereNull('parent_item_id');
        //         $query->orWhere('parent_item_id', '0');
        //     }) ->Where('is_published', '=',1)
        //         ->whereIN('category_id', $categoryIds)
        //         ->groupBy('store_id')
        //         ->paginate(10);

        //     $itemsIDS = [];
        //     foreach ($cItems as $item) {
        //         $data[] = $item;
        //     }
        //     // Category items end

        //     // occasion items start
        //     $followedOccasion = \OlaHub\UserPortal\Models\Following::where("user_id", app('session')->get('tempID'))->where('type', 4)->get();
        //     $occasionIds = [];
        //     foreach ($followedOccasion as $followedOccasionID) {
        //         $occasionIds[] = $followedOccasionID->target_id;
        //     }

        //     $oItems = \OlaHub\UserPortal\Models\CatalogItem::join('catalog_item_occasions', 'catalog_item_occasions.item_id', 'catalog_items.id')
        //         ->select('catalog_item_occasions.occasion_id', 'catalog_items.*')
        //         ->whereHas('quantityData', function ($q) {
        //         $q->where('quantity', '>', 0);
        //     })->where(function ($query) {
        //         $query->whereNull('catalog_items.parent_item_id');
        //         $query->orWhere('catalog_items.parent_item_id', '0');
        //     })->Where('catalog_items.is_published', '=',1)
        //         ->whereIn('occasion_id', $occasionIds)
        //         ->groupBy('store_id')
        //         ->paginate(10);
        //     foreach ($oItems as $item) {
        //         $data[] = $item;
        //     }
        //     // occasion items end

        //     // brand items start
        //     $followedBrands = \OlaHub\UserPortal\Models\Following::where("user_id", app('session')->get('tempID'))->where('type', 1)->get();
        //     $brandIds = [];
        //     foreach ($followedBrands as $followedBrandsID) {
        //         $brandIds[] = $followedBrandsID->target_id;
        //     }

        //     $oItems = \OlaHub\UserPortal\Models\CatalogItem::whereHas('quantityData', function ($q) {
        //             $q->where('quantity', '>', 0);
        //         })->where(function ($query) {
        //             $query->whereNull('catalog_items.parent_item_id');
        //             $query->orWhere('catalog_items.parent_item_id', '0');
        //         })->Where('is_published', '=',1)
        //         ->whereIn('brand_id', $brandIds)
        //         ->groupBy('store_id')
        //         ->paginate(10);
        //     foreach ($oItems as $item) {
        //         $data[] = $item;
        //     }
        //     // brand items end
        //     $data = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($data, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler');

            // designer items start
            // $followedDesigner = \OlaHub\UserPortal\Models\Following::where("user_id", app('session')->get('tempID'))->where('type', 2)->get();
            // $designerIds = [];
            // foreach ($followedDesigner as $followedDesignerID) {
            //     $designerIds[] = $followedDesignerID->target_id;
            // }

            // $dItems = \OlaHub\UserPortal\Models\DesignerItems::where(function ($query) {
            //     $query->whereNull('parent_item_id');
            //     $query->orWhere('parent_item_id', '0');
            // }) ->Where('is_published', '=',1)
            //     ->where('item_stock', '>', 0)
            //     ->inRandomOrder()
            //     ->whereIn('designer_id', $designerIds)
            //     ->paginate(10);

            // foreach ($dItems as $item2) {
            //     $dataNew = $this->handleDesignerItem($item2);
            //     array_push($data['data'], $dataNew);

            // }

            // designer items end
            // shuffle($data['data']);

        // }
        // if(app('session')->get('tempID') == NUll || count($data['data']) < 1){

            $itemModel = (new \OlaHub\UserPortal\Models\CatalogItem)->newQuery();
            $itemModel->whereHas('quantityData', function ($q) {
                $q->where('quantity', '>', 0);
            })->where(function ($query) {
                $query->whereNull('parent_item_id');
                $query->orWhere('parent_item_id', '0');
            });
            $itemModel->Where('is_published', '=',1);
            $itemModel->orderBy('total_views', 'DESC');
            $itemModel->orderBy('name', 'ASC');
            $itemModel->take(6);
            $items = $itemModel->get();

            if ($items->count() < 1) {

                throw new NotAcceptableHttpException(404);
            }
            $data = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($items, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler');

        // }

        $data['status'] = true;
        $data['code'] = 200;
        $log->setLogSessionData(['response' => $data]);
        $log->saveLogSessionData();
        return response($data, 200);
    }
    private function handleDesignerItem($data)
    {
        $return = [
            "type"=>"designer",
            "productID" => isset($data->id) ? $data->id : 0,
            "productSlug" => isset($data->item_slug) ? $data->item_slug : null,
            "productName" => isset($data->name) ? $data->name : null,
            "productDescription" => isset($data->description) ? $data->description : null,
            "productInStock" => isset($data->item_stock) ? $data->item_stock : 0,
            "productPrice" => isset($data->price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($data->price, true) : 0,
            "productOwner" => isset($data->designer_id) ? $data->designer_id : 0,
            "productOwnerName" => isset($data->designer) ? $data->designer->brand_name : 0,
            "productOwnerSlug" => isset($data->designer) ? $data->designer->designer_slug : 0,
            "productSlug" =>\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($data, 'item_slug', $data->name),
            "productShowLabel" => true,
        ];
        $itemPrice = DesignerItems::checkPrice($data);
        $return['productPrice'] = $itemPrice['productPrice'];
        $return['productDiscountedPrice'] = $itemPrice['productDiscountedPrice'];
        $return['productHasDiscount'] = $itemPrice['productHasDiscount'];

        $images = $data->images;
        if ($images->count() > 0) {
            $return['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            $return['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }


        return $return;
    }

    public function getMostOfferData()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getMostOfferData"]);
        $items = [];
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
        $itemModel->where('is_published','=',1);
        $itemModel->orderByRaw("RAND()");
        $itemModel->take(6);
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

    public function getRecommendedData()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getRecommendedData"]);
        $items = [];
        if(app('session')->get('tempID')) {
            // intrest items
            $getfollowedInterests =  \OlaHub\UserPortal\Models\UserModel::where("id", app('session')->get('tempID'))->select('interests')->get();
            $followedInterests = explode(',', $getfollowedInterests[0]->interests);

            $interestsItems = \OlaHub\UserPortal\Models\CatalogItem::join('catalog_item_interests', 'catalog_item_interests.item_id', 'catalog_items.id')
                ->select('catalog_item_interests.interest_id', 'catalog_items.*')
                ->whereHas('quantityData', function ($q) {
                $q->where('quantity', '>', 0);
            })->where(function ($query) {
                $query->whereNull('catalog_items.parent_item_id');
                $query->orWhere('catalog_items.parent_item_id', '0');
            })
                ->Where('catalog_items.is_published', '=',1)
                ->whereIn('interest_id', $followedInterests)
                ->orderByRaw("RAND()")
                ->limit(6)
                ->get();
            foreach ($interestsItems as $item){
                $items[]=$item;
            }
            if( count($interestsItems) < 6 ){
                $itemModel = \OlaHub\UserPortal\Models\CatalogItem::whereHas('quantityData', function ($q) {
                    $q->where('quantity', '>', 0);
                })->where(function ($query) {
                    $query->whereNull('catalog_items.parent_item_id');
                    $query->orWhere('catalog_items.parent_item_id', '0');
                })
                    ->Where('admin_recommended','=',0)->orderByRaw("RAND()")->take(6)->get();
                if ($itemModel->count() < 1) {
                    throw new NotAcceptableHttpException(404);
                }
                foreach ($itemModel as $item){
                    $items[]=$item;
                }

            }
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($items, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler');

        }

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
