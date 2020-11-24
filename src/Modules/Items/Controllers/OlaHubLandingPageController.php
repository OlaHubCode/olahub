<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // offers
        $itemModel = (new \OlaHub\UserPortal\Models\CatalogItem)->newQuery();
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
        if (app('session')->get('tempID')) {

            $followedCategory = \OlaHub\UserPortal\Models\Following::where("user_id", app('session')->get('tempID'))->where('type', 3)->pluck('target_id')->toArray();
            $followedBrands = \OlaHub\UserPortal\Models\Following::where("user_id", app('session')->get('tempID'))->where('type', 1)->pluck('target_id')->toArray();
            $followedOccasion = \OlaHub\UserPortal\Models\Following::where("user_id", app('session')->get('tempID'))->where('type', 4)->pluck('target_id')->toArray();

            $cItems = \OlaHub\UserPortal\Models\CatalogItem::whereHas('quantityData', function ($q) {
                $q->where('quantity', '>', 0);
            })
                ->where(function ($query) {
                    $query->whereNull('parent_item_id');
                    $query->orWhere('parent_item_id', '0');
                })
                ->where(function ($query2) use ($followedCategory, $followedBrands, $followedOccasion) {
                    $query2->WhereHas("category", function ($q1) use ($followedCategory) {
                        $q1->whereIN('id', $followedCategory);
                        $q1->orWhereHas("parentCategory", function ($q2) use ($followedCategory) {
                            $q2->whereIN('id', $followedCategory);
                        });
                    });
                    $query2->orWhereIn('brand_id', $followedBrands);
                    $query2->orWhereHas("occasionSync", function ($q3) use ($followedOccasion) {
                        $q3->whereIN('occasion_id', $followedOccasion);
                    });
                })->inRandomOrder()->get()->unique('store_id')->take(6);

            foreach ($cItems as $item) {
                $data[] = $item;
            }

            if (count($data) < 6) {
                $count = 6 - count($data);
                $itemModel = (new \OlaHub\UserPortal\Models\CatalogItem)->newQuery();
                $itemModel->whereHas('quantityData', function ($q) {
                    $q->where('quantity', '>', 0);
                })->where(function ($query) {
                    $query->whereNull('parent_item_id');
                    $query->orWhere('parent_item_id', '0');
                })->where('is_published', '=', 1)
                    ->orderBy('total_views', 'DESC')
                    ->orderBy('name', 'ASC')
                    ->take($count);
                $items = $itemModel->get();
                foreach ($items as $item) {
                    $data[] = $item;
                }
            }

            $data = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($data, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler');
            shuffle($data['data']);
        } else {
            $itemModel = (new \OlaHub\UserPortal\Models\CatalogItem)->newQuery();
            $itemModel->whereHas('quantityData', function ($q) {
                $q->where('quantity', '>', 0);
            })->where(function ($query) {
                $query->whereNull('parent_item_id');
                $query->orWhere('parent_item_id', '0');
            })->where('is_published', '=', 1)
                ->orderBy('total_views', 'DESC')
                ->orderBy('name', 'ASC')
                ->take(6);
            $items = $itemModel->get();
            $data = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($items, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler');
        }

        $data['status'] = true;
        $data['code'] = 200;
        $log->setLogSessionData(['response' => $data]);
        $log->saveLogSessionData();

        if (strpos($this->userAgent, "application-") === false)
            return $data['data'];
        else
            return response($data, 200);
    }
    private function handleDesignerItem($data)
    {
        $return = [
            "type" => "designer",
            "productID" => isset($data->id) ? $data->id : 0,
            "productSlug" => isset($data->item_slug) ? $data->item_slug : null,
            "productName" => isset($data->name) ? $data->name : null,
            "productDescription" => isset($data->description) ? $data->description : null,
            "productInStock" => isset($data->item_stock) ? $data->item_stock : 0,
            "productPrice" => isset($data->price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($data->price, true) : 0,
            "productOwner" => isset($data->designer_id) ? $data->designer_id : 0,
            "productOwnerName" => isset($data->designer) ? $data->designer->brand_name : 0,
            "productOwnerSlug" => isset($data->designer) ? $data->designer->designer_slug : 0,
            "productSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($data, 'item_slug', $data->name),
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
        $itemModel->where('is_published', '=', 1);
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

        if (strpos($this->userAgent, "application-") === false)
            return @$return['data'];
        else
            return response($return, 200);
    }

    public function getHomeData()
    {
        $return['trending'] = $this->getTrendingData();
        $return['offers'] = $this->getMostOfferData();
        $return['recommended'] = $this->getRecommendedData();
        $return['status'] = true;
        $return['code'] = 200;
        return response($return, 200);
    }
    public function getRecommendedData()
    {
        $items = [];
        if (app('session')->get('tempID')) {
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
                ->Where('catalog_items.is_published', '=', 1)
                ->whereIn('interest_id', $followedInterests)
                ->orderByRaw("RAND()")
                ->limit(6)
                ->get();
            foreach ($interestsItems as $item) {
                $items[] = $item;
            }
            if (count($interestsItems) < 6) {
                $itemModel = \OlaHub\UserPortal\Models\CatalogItem::whereHas('quantityData', function ($q) {
                    $q->where('quantity', '>', 0);
                })->where(function ($query) {
                    $query->whereNull('catalog_items.parent_item_id');
                    $query->orWhere('catalog_items.parent_item_id', '0');
                })
                    ->Where('admin_recommended', '=', 0)->orderByRaw("RAND()")->take(6)->get();
                if ($itemModel->count() < 1) {
                    throw new NotAcceptableHttpException(404);
                }
                foreach ($itemModel as $item) {
                    $items[] = $item;
                }
            }
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($items, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler');
        }

        // $return['status'] = true;
        // $return['code'] = 200;
        // $log->setLogSessionData(['response' => $return]);
        // $log->saveLogSessionData();
        // return response($return, 200);
        return @$return['data'];
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
        $classesMainModel->where('is_main', $type);
        $classesMainModel->whereHas('itemsMainData', function ($query) {
            $query->where('is_published', '1');
        });
        return $classesMainModel->get();
    }
}
