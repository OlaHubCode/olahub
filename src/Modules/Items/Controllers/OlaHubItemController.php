<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Unique;
use OlaHub\UserPortal\Helpers\DesginerItemsHelper;
use OlaHub\UserPortal\Models\CatalogItem;
use OlaHub\UserPortal\Models\DesignerItems;
use OlaHub\UserPortal\Models\ItemAttrValue;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class OlaHubItemController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    protected $itemsModel;
    protected $requestSort;
    protected $uploadImage;
    private $first = false;
    private $force = false;
    protected $userAgent;
    protected $allItemsModel; // to set catalog and designers modals
    protected $allItemsCategories = []; // to set catalog and designers categories
    private $itemsType;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->requestSort = $return['requestSort'];
        $this->uploadImage = $request->all();
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
        $this->itemsType = @$this->requestFilter['type'];
    }

    public function getItemsData()
    {
        $target = NULL;
        $catalogItems = [];
        $designerItems = [];
        $allItems = [];
        $meta = NULL;

        if (!empty($this->requestFilter['brandSlug']) || !empty($this->requestFilter['brands']))
            $target = "brands";
        if (!empty($this->requestFilter['designerSlug']) || !empty($this->requestFilter['designers']))
            $target = "designers";

        if (!$target || $target == 'brands')
            $catalogItems = $this->fetchAllItemsData();
        if (!$target || $target == 'designers')
            $designerItems = $this->fetchAllItemsData("designer_items");

        $meta = (count($catalogItems) ? $catalogItems["meta"] : $designerItems["meta"]);
        if (count($catalogItems))
            $catalogItems = $catalogItems["data"];
        if (count($designerItems)) {
            if ($designerItems["meta"]["pagination"]["total_pages"] > $meta["pagination"]["total_pages"])
                $meta = $designerItems["meta"];
            $designerItems = $designerItems["data"];
        }

        $allItems = array_merge($catalogItems, $designerItems);
        // shuffle($allItems);

        $return['status'] = true;
        $return['meta'] = $meta;
        $return['data'] = $allItems;
        $return['code'] = 200;
        $return['categories'] = $this->mergeCategories();
        return response($return, 200);
    }
    public function fetchAllItemsData($tableName = "catalog_items")
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "ItemsCriatria"]);

        $this->allItemsModel = $tableName == "catalog_items" ?
            (new CatalogItem)->newQuery() : (new DesignerItems)->newQuery();
        if (isset($this->requestFilter['priceFrom']) && strlen($this->requestFilter['priceFrom']) > 0) {
            $this->allItemsModel->where(function ($query) {
                $query->where(function ($q) {
                    $q->Where('discounted_price_end_date', '<', date('Y-m-d') . " 23:59:59");
                    $q->where('price', ">=", (float) $this->requestFilter['priceFrom']);
                })->orWhere(function ($q) {
                    $q->WhereNull('discounted_price_end_date');
                    $q->where('price', ">=", (float) $this->requestFilter['priceFrom']);
                })->orWhere(function ($q) {
                    $q->whereNotNull('discounted_price_start_date');
                    $q->whereNotNull('discounted_price_end_date');
                    $q->where('discounted_price_start_date', '<=', date('Y-m-d') . " 00:00:01");
                    $q->where('discounted_price_end_date', '>=', date('Y-m-d') . " 23:59:59");
                    $q->where('discounted_price', ">=", (float) $this->requestFilter['priceFrom']);
                });
            });
        }
        if (isset($this->requestFilter['priceTo']) && strlen($this->requestFilter['priceTo']) > 0) {
            $this->allItemsModel->where(function ($query) {
                $query->where(function ($q) {
                    $q->where(function ($qWhere) {
                        $qWhere->Where('discounted_price_end_date', '<', date('Y-m-d') . " 23:59:59");
                        $qWhere->orWhereNull('discounted_price_end_date');
                    });
                    $q->where('price', "<=", (float) $this->requestFilter['priceTo']);
                })->orWhere(function ($q) {
                    $q->whereNotNull('discounted_price_start_date');
                    $q->whereNotNull('discounted_price_end_date');
                    $q->where('discounted_price_start_date', '<=', date('Y-m-d') . " 00:00:01");
                    $q->where('discounted_price_end_date', '>=', date('Y-m-d') . " 23:59:59");
                    $q->where('discounted_price', "<=", (float) $this->requestFilter['priceTo']);
                });
            });
        }
        if (isset($this->requestFilter['offerOnly']) && $this->requestFilter['offerOnly']) {
            $this->allItemsModel->where(function ($query) {
                $query->whereNotNull('discounted_price_start_date');
                $query->whereNotNull('discounted_price_end_date');
                $query->where('discounted_price_start_date', '<=', date('Y-m-d') . " 00:00:01");
                $query->where('discounted_price_end_date', '>=', date('Y-m-d') . " 23:59:59");
            });
        }
        if (count($this->requestFilter) > 0 && ($this->force == true || (isset($this->requestFilter['all']) && (string) $this->requestFilter['all'] == "0"))) {
            // unset($this->requestFilter['all']);
            if (isset($this->requestFilter['attributes']) && count($this->requestFilter['attributes']) > 0) {
                $this->allItemsModel->whereHas('valuesData', function ($query) {
                    $query->whereHas('valueMainData', function ($query1) {
                        $query1->whereIn('id', $this->requestFilter['attributes']);
                    });
                });
            }
            $filters = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingRequestFilter($this->requestFilter, CatalogItem::$columnsMaping);

            foreach ($filters['main'] as $input => $value) {
                if (is_array($value) && count($value)) {
                    $this->allItemsModel->whereIn($input, $value);
                } elseif (is_string($value) && strlen($value) > 0) {
                    $this->allItemsModel->where($input, $value);
                }
            }
            foreach ($filters['relations'] as $model => $data) {
                if ($model == 'brand') {
                    $this->allItemsModel->selectRaw("catalog_items.*, merchant_stors.name as brand_name, SUM(catalog_item_stors.quantity) as qu")
                        ->leftJoin("catalog_item_stors", "catalog_item_stors.item_id", "=", "catalog_items.id");
                    $this->allItemsModel->leftJoin("merchant_stors", "merchant_stors.id", "=", "catalog_items.store_id");
                    foreach ($data as $input => $value) {
                        $this->allItemsModel->where("merchant_stors." . $input, $value);
                    }
                } elseif ($model == "designer") {
                    $this->allItemsModel->selectRaw("designer_items.*")
                        ->leftJoin("designers", "designers.id", "=", "designer_items.designer_id");
                    foreach ($data as $input => $value) {
                        $this->allItemsModel->where("designers." . $input, $value);
                    }
                } else {
                    $this->allItemsModel->whereHas($model, function ($q) use ($data) {
                        foreach ($data as $input => $value) {
                            if (is_array($value) && count($value)) {
                                $q->whereIn($input, $value);
                            } elseif (is_string($value) && strlen($value) > 0) {
                                $q->where($input, $value);
                            }
                        }
                    });
                }
            }
        }
        $this->allItemsModel->groupBy($tableName . '.id');
        if (!(isset($this->requestFilter['attributes']) && count($this->requestFilter['attributes']) > 0)) {
            $this->allItemsModel->where(function ($query) use ($tableName) {
                $query->whereNull($tableName . '.parent_item_id');
                $query->orWhere($tableName . '.parent_item_id', '0');
            });
            $this->first = true;
        }
        $column = 'created_at';
        $type = 'DESC';
        if ($this->requestSort) {
            $order = explode('-', $this->requestSort);
            if (count($order) == 2 && isset($order[0]) && isset($order[1])) {
                switch ($order[0]) {
                    case 'create':
                        $column = 'created_at';
                        break;
                    case 'name':
                        $column = 'name';
                        break;
                    case 'price':
                        if (isset($this->requestFilter['offerOnly']) && $this->requestFilter['offerOnly']) {
                            $column = 'discounted_price';
                        } else {
                            $column = 'price';
                        }
                        break;
                    case 'discountedPrice':
                        $column = 'discounted_price';
                        break;
                }
                if (in_array($order[1], ['asc', 'desc'])) {
                    $type = strtoupper($order[1]);
                }
            }
        }

        // Categories
        $target = ($tableName == "catalog_items" ? "itemsMainData" : "itemsDesignerData");
        $itemsIDs = $this->allItemsModel->pluck('id');
        $categoryModel = (new \OlaHub\UserPortal\Models\ItemCategory)->newQuery();

        $categoryModel->where(function ($wherQ) use ($itemsIDs, $target) {
            $wherQ->where(function ($ww) {
                $ww->whereNull('parent_id')
                    ->orWhere('parent_id', '0');
            });
            $wherQ->whereHas($target, function ($q) use ($itemsIDs) {
                $q->whereIn('id', $itemsIDs);
            });
        });

        $categoryModel->orWhere(function ($wherQ) use ($itemsIDs, $target) {
            if ($target == "itemsMainData") {
                $wherQ->whereHas('childsData', function ($childQ) use ($itemsIDs, $target) {
                    $childQ->whereHas($target, function ($q) use ($itemsIDs) {
                        $q->whereIn('id', $itemsIDs);
                    });
                });
            } else {
                $wherQ->whereHas($target, function ($q) use ($itemsIDs) {
                    $q->whereIn('id', $itemsIDs);
                });
            }
        });

        $categoryModel->groupBy('id');
        $categories = $categoryModel->get();
        $categories = \OlaHub\UserPortal\Models\ItemCategory::setReturnResponse($categories, $itemsIDs)["data"];
        $this->allItemsCategories = array_merge($this->allItemsCategories, $categories);

        // Items
        $this->allItemsModel->orderBy($column, $type);
        $items = $this->allItemsModel->paginate(20);

        return $tableName == "catalog_items" ?
            \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($items, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler') :
            \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($items, '\OlaHub\UserPortal\ResponseHandlers\DesginerItemsHandler');
    }
    private function mergeCategories()
    {
        $cats = [];
        $cc = [];
        $tempCats = [];
        foreach ($this->allItemsCategories as $c) {
            if (!in_array($c['classID'], $tempCats)) {
                $tempCats[] = $c['classID'];
                $cc[$c['classID']] = $c;
            } else {
                if (!count($cc[$c['classID']]['childsData'])) {
                    $cc[$c['classID']]['childsData'] = $c['classID']['childsData'];
                }
            }
        }
        foreach ($cc as $c)
            $cats[] = $c;
        return $cats;
    }
    private function mergeAttributes($attrs)
    {
        $attr = [];
        $aa = [];
        $tempAtrrs = [];
        foreach ($attrs as $a) {
            if (!in_array($a['valueID'], $tempAtrrs)) {
                $tempAtrrs[] = $a['valueID'];
                $aa[$a['valueID']] = $a;
            } else {
                foreach ($a['childsData'] as $cd) {
                    if (!in_array($cd, $aa[$a['valueID']])) {
                        $aa[$a['valueID']]['childsData'][] = $cd;
                    }
                }
            }
        }
        foreach ($aa as $a)
            if (count($a['childsData'])) {
                $attr[] = $a;
            }
        return $attr;
    }
    public function getCatsData($all = false)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getItemFiltersCatsData"]);

        ////////////////////////////////////////////////

        $this->allItemsModel = (new CatalogItem)->newQuery();
        if (isset($this->requestFilter['priceFrom']) && strlen($this->requestFilter['priceFrom']) > 0) {
            $this->allItemsModel->where(function ($query) {
                $query->where(function ($q) {
                    $q->Where('discounted_price_end_date', '<', date('Y-m-d') . " 23:59:59");
                    $q->where('price', ">=", (float) $this->requestFilter['priceFrom']);
                })->orWhere(function ($q) {
                    $q->WhereNull('discounted_price_end_date');
                    $q->where('price', ">=", (float) $this->requestFilter['priceFrom']);
                })->orWhere(function ($q) {
                    $q->whereNotNull('discounted_price_start_date');
                    $q->whereNotNull('discounted_price_end_date');
                    $q->where('discounted_price_start_date', '<=', date('Y-m-d') . " 00:00:01");
                    $q->where('discounted_price_end_date', '>=', date('Y-m-d') . " 23:59:59");
                    $q->where('discounted_price', ">=", (float) $this->requestFilter['priceFrom']);
                });
            });
        }

        if (isset($this->requestFilter['priceTo']) && strlen($this->requestFilter['priceTo']) > 0) {
            $this->allItemsModel->where(function ($query) {
                $query->where(function ($q) {
                    $q->where(function ($qWhere) {
                        $qWhere->Where('discounted_price_end_date', '<', date('Y-m-d') . " 23:59:59");
                        $qWhere->orWhereNull('discounted_price_end_date');
                    });
                    $q->where('price', "<=", (float) $this->requestFilter['priceTo']);
                })->orWhere(function ($q) {
                    $q->whereNotNull('discounted_price_start_date');
                    $q->whereNotNull('discounted_price_end_date');
                    $q->where('discounted_price_start_date', '<=', date('Y-m-d') . " 00:00:01");
                    $q->where('discounted_price_end_date', '>=', date('Y-m-d') . " 23:59:59");
                    $q->where('discounted_price', "<=", (float) $this->requestFilter['priceTo']);
                });
            });
        }

        if (isset($this->requestFilter['offerOnly']) && $this->requestFilter['offerOnly']) {
            $this->allItemsModel->where(function ($query) {
                $query->whereNotNull('discounted_price_start_date');
                $query->whereNotNull('discounted_price_end_date');
                $query->where('discounted_price_start_date', '<=', date('Y-m-d') . " 00:00:01");
                $query->where('discounted_price_end_date', '>=', date('Y-m-d') . " 23:59:59");
            });
        }

        if (count($this->requestFilter) > 0 && ($this->force == true || (isset($this->requestFilter['all']) && (string) $this->requestFilter['all'] == "0"))) {
            unset($this->requestFilter['all']);
            if (isset($this->requestFilter['attributes']) && count($this->requestFilter['attributes']) > 0) {
                $attributes = [];
                foreach ($this->requestFilter['attributes'] as $one) {
                    $attrData = \OlaHub\UserPortal\Models\AttrValue::find($one);
                    if ($attrData) {
                        $attributes[$attrData->product_attribute_id][] = $one;
                    }
                }
                foreach ($attributes as $key => $values) {
                    $this->allItemsModel->join("catalog_item_attribute_values as ciav$key", "ciav$key.item_id", "=", "catalog_items.id");
                    $this->allItemsModel->whereIn("ciav$key.item_attribute_value_id", $values);
                }

                $this->allItemsModel->select("catalog_items.*");
            }

            $filters = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingRequestFilter($this->requestFilter, CatalogItem::$columnsMaping);
            foreach ($filters['main'] as $input => $value) {
                if (is_array($value) && count($value)) {
                    $this->allItemsModel->whereIn($input, $value);
                } elseif (is_string($value) && strlen($value) > 0) {
                    $this->allItemsModel->where($input, $value);
                }
            }
            foreach ($filters['relations'] as $model => $data) {
                // if ($model == "interests" && isset($this->requestFilter['attributes']) && count($this->requestFilter['attributes']) > 0 && isset($data["interest_slug"])) {
                //     $interest = \OlaHub\UserPortal\Models\Interests::where("interest_slug", $data["interest_slug"])->first();
                //     if ($interest && count($interest->items) > 0) {
                //         $this->allItemsModel->whereIn("catalog_items.id", $interest->items);
                //     } else {
                //         $this->allItemsModel->where("catalog_items.id", 0);
                //     }
                // } else {
                if ($model == 'brand') {
                    $this->allItemsModel->selectRaw("catalog_items.*, merchant_stors.name as brand_name, SUM(catalog_item_stors.quantity) as qu")
                        ->leftJoin("catalog_item_stors", "catalog_item_stors.item_id", "=", "catalog_items.id");
                    $this->allItemsModel->leftJoin("merchant_stors", "merchant_stors.id", "=", "catalog_items.store_id");
                    foreach ($data as $input => $value) {
                        $this->allItemsModel->where("merchant_stors." . $input, $value);
                    }
                } else {
                    $same = true;
                    $this->allItemsModel->whereHas($model, function ($q) use ($data, $same) {
                        foreach ($data as $input => $value) {
                            if (is_array($value) && count($value)) {
                                $same ? $q->whereIn($input, $value) : $q->whereNotIn($input, $value);
                            } elseif (is_string($value) && strlen($value) > 0) {
                                $same ? $q->where($input, $value) : $q->where($input, '!=', $value);
                            }
                        }
                    });
                }
                // }
            }
        }
        $this->allItemsModel->groupBy('catalog_items.id');
        if (!(isset($this->requestFilter['attributes']) && count($this->requestFilter['attributes']) > 0)) {
            $this->allItemsModel->where(function ($query) {
                $query->whereNull('catalog_items.parent_item_id');
                $query->orWhere('catalog_items.parent_item_id', '0');
            });
            $this->first = true;
        }
        /////////////////////////////////////////
        $itemsIDs = $this->allItemsModel->pluck('id');
        $categoryModel = (new \OlaHub\UserPortal\Models\ItemCategory)->newQuery();
        $categoryModel->where(function ($wherQ) use ($itemsIDs) {
            $wherQ->where(function ($ww) {
                $ww->whereNull('parent_id')
                    ->orWhere('parent_id', '0');
            });
            $wherQ->whereHas('itemsMainData', function ($q) use ($itemsIDs) {
                $q->whereIn('id', $itemsIDs);
            });
        });
        $categoryModel->orWhere(function ($wherQ) use ($itemsIDs) {
            $wherQ->whereHas('childsData', function ($childQ) use ($itemsIDs) {
                $childQ->whereHas('itemsMainData', function ($q) use ($itemsIDs) {
                    $q->whereIn('id', $itemsIDs);
                });
            });
        });
        $categoryModel->groupBy('id');
        if ($all) {
            $categories = $categoryModel->get();
        } else {
            $categories = $categoryModel->paginate(5);
        }
        if ($categories->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return = \OlaHub\UserPortal\Models\ItemCategory::setReturnResponse($categories, $itemsIDs);
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }
    public function getItems()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getItems"]);

        $this->ItemsCriatria();
        $this->sortItems();
        $items = $this->itemsModel->paginate(20);
        if ($items->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($items, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler');
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }
    public function getVoucherItems()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getVoucherItems"]);

        $items = CatalogItem::where('is_voucher', 1)->get();
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
    public function getAlsoLikeItems()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getAlsoLikeItems"]);

        $this->itemsModel = (new CatalogItem)->newQuery();
        $this->itemsModel->where("is_voucher", "0");
        $this->itemsModel->where(function ($query) {
            $query->where('parent_item_id', "0");
            $query->orWhereNull('parent_item_id');
        });
        $items = $this->itemsModel->orderByRaw("RAND()")->take(6)->get();
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
    public function getOneItem($slug)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getOneItem"]);
        $this->force = true;

        $model =  (new CatalogItem);
        $this->itemsModel = (new CatalogItem)->newQuery();
        $item_type = "catalog";
        $tableName = "catalog_items";

        if ($this->requestFilter['type'] == "designer") {
            $model = (new DesignerItems);
            $this->itemsModel = (new DesignerItems)->newQuery();
            $item_type = "designer";
            $tableName = "designer_items";
        }
        $this->oneItemsCriatria($item_type);
        if (isset($this->requestFilter['attributes']) && count($this->requestFilter['attributes']) < 1) {
            $this->itemsModel->where('item_slug', $slug);
        } else {
            $parent = $model->where('item_slug', $slug)->first();
            $pID = $parent->parent_item_id ?? $parent->id;
            if ($parent->parent_item_id > 0) {
                $this->itemsModel->where(function ($q) use ($pID, $tableName) {
                    $q->where($tableName . '.parent_item_id', $pID);
                    $q->orWhere($tableName . '.id', $pID);
                });
            } else {
                $childs = $model->where($tableName . '.parent_item_id', $parent->id)->pluck('id')->toArray();
                $childs[] = $parent->id;
                $this->itemsModel->whereIn($tableName . '.id', $childs);
            }
        }
        $item = $this->itemsModel->first();

        if (!$item) {
            throw new NotAcceptableHttpException(404);
        }
        if ($item_type == 'catalog') {
            \OlaHub\UserPortal\Models\CatalogItemViews::setItemView($item);
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($item, "\OlaHub\UserPortal\ResponseHandlers\ItemResponseHandler");
        } else {
            $return['data'] = (new DesginerItemsHelper)->getOneItemData($item, $slug, $this->requestFilter);
        }

        if (isset($this->requestFilter['celebrationId']) && $this->requestFilter['celebrationId']) {
            $existInCelebration = FALSE;
            $existCelebration = TRUE;
            $acceptParticipant = FALSE;
            $celebrationCart = \OlaHub\UserPortal\Models\Cart::withoutGlobalScope('countryUser')->where('celebration_id', $this->requestFilter['celebrationId'])->first();
            if ($celebrationCart) {
                $cartItem = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')
                    ->where('shopping_cart_id', $celebrationCart->id)
                    ->where("item_type", $item_type)
                    ->where('item_id', $item->id)->first();
                if ($cartItem) {
                    $existInCelebration = TRUE;
                }
            } else {
                $existCelebration = FALSE;
            }
            $participant = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $this->requestFilter['celebrationId'])->where('is_approved', 1)->where('user_id', app('session')->get('tempID'))->first();
            if ($participant) {
                $acceptParticipant = TRUE;
            }
            $return['data']["existCelebration"] = $existCelebration;
            $return['data']["existInCelebration"] = $existInCelebration;
            $return['data']["acceptParticipant"] = $acceptParticipant;
        }
        if (isset($this->requestFilter['registryId']) && $this->requestFilter['registryId']) {
            $existInRegistry = FALSE;
            $existRegistry = TRUE;
            $acceptParticipant = FALSE;
            $registry = \OlaHub\UserPortal\Models\RegistryModel::where('id', $this->requestFilter['registryId'])->first();
            if ($registry) {
                $registryItem = \OlaHub\UserPortal\Models\RegistryGiftModel::where('registry_id', $registry->id)
                    ->where("item_type", $item_type)
                    ->where('item_id', $item->id)->first();
                if ($registryItem) {
                    $existInRegistry = TRUE;
                }
            } else {
                $existRegistry = FALSE;
            }
            if ($registry->user_id == app('session')->get('tempID')) {
                $acceptParticipant = TRUE;
            }
            $return['data']["existRegistry"] = $existRegistry;
            $return['data']["existInRegistry"] = $existInRegistry;
            $return['data']["acceptParticipant"] = $acceptParticipant;
        }
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getOneItemAttrsData($slug, $all = false)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getOneItemAttrsData"]);

        $model =  (new CatalogItem);
        $itemsTarget = "valueItemsData";
        $itemsData = "itemsMainData";

        if ($this->itemsType == "designer") {
            $model = (new DesignerItems);
            $itemsTarget = "valueDesignerData";
            $itemsData = "itemsMainData";
        }

        $item = $model->newQuery()->where('item_slug', $slug)->first();
        if ($item->parent_item_id > 0) {
            $itemsIDs = [$item->parent_item_id];
        } else {
            $itemsIDs = [$item->id];
        }
        $attributes = NULL;

        $attributes = \OlaHub\UserPortal\Models\Attribute::whereHas('valuesData', function ($values) use ($itemsIDs, $itemsTarget, $itemsData) {
            $values->whereHas($itemsTarget, function ($q) use ($itemsIDs, $itemsData) {
                $q->whereIn('parent_item_id', $itemsIDs);
                $q->whereHas($itemsData, function ($q2) {
                    $q2->where("is_published", "1");
                });
            })->whereNotIn('product_attribute_id', $this->requestFilter['attributesParent']);
        })->groupBy('id')->get();
        $return = \OlaHub\UserPortal\Models\Attribute::setOneProductReturnResponse($attributes, $itemsIDs, true, $itemsTarget);

        // if ($attributes->count() < 1) {
        //     throw new NotAcceptableHttpException(404);
        // }

        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getOneItemRelatedItems($slug)
    {

        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getOneItemRelatedItems"]);

        $model =  (new CatalogItem);
        $tableName = "catalog_items";
        $item_type = "store";

        if ($this->requestFilter == "designer") {
            $model = (new DesignerItems);
            $tableName = "designer_items";
            $item_type = "designer";
        }

        $item = $model->newQuery()->where('item_slug', $slug)->first();
        if (!$item) {
            throw new NotAcceptableHttpException(404);
        }
        $itemID = $item->id;
        if ($item->parent_item_id > 0) {
            $itemID = $item->parent_item_id;
        }
        $items = $model->newQuery()->where('id', '!=', $itemID);

        if ($item_type == 'store') {
            $items->where("is_voucher", "0")->where(function ($query) use ($item) {
                $query->where('category_id', $item->category_id)
                    ->orWhere('merchant_id', $item->merchant_id)
                    ->orWhere('store_id', $item->store_id);
            });
        } else {
            $items->where('designer_id', $item->designer_id);
        }
        $items->where(function ($query2) use ($tableName) {
            $query2->whereNull($tableName . '.parent_item_id');
            $query2->orWhere($tableName . '.parent_item_id', '0');
        });

        $items->groupBy('id')->inRandomOrder()->take(5);

        $handlerType = 'ItemsListResponseHandler';
        if ($item_type != 'store')
            $handlerType = 'DesginerItemsHandler';

        $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($items->get(), "\OlaHub\UserPortal\ResponseHandlers\\$handlerType");
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getOneItemMostViewedItems($slug)
    {

        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getOneItemMostViewedItems"]);

        $this->itemsModel = (new CatalogItem)->newQuery();
        $this->itemsModel->where('item_slug', $slug);
        $item = $this->itemsModel->first();
        if (!$item) {
            throw new NotAcceptableHttpException(404);
        }
        $itemID = $item->id;
        if ($item->parent_item_id > 0) {
            $itemID = $item->parent_item_id;
        }

        if (app('session')->get('tempID') != null) {
            $user = \OlaHub\UserPortal\Models\UserModel::where('id', app('session')->get('tempID'))->first();
            if (count($user->catalogItemViews) > 0) {
                $ids = $user->catalogItemViews->pluck('item_id')->toArray();
                $itemModel = (new \OlaHub\UserPortal\Models\CatalogItem)->newQuery();
                $itemModel->where('id', '!=', $itemID);
                $itemModel->whereHas('quantityData', function ($q) {
                    $q->where('quantity', '>', 0);
                })->where(function ($query) {
                    $query->whereNull('parent_item_id');
                    $query->orWhere('parent_item_id', '0');
                })->where('is_published', '=', 1)
                    ->whereIn('id', $ids);
                $itemModel->orderBy('updated_at', 'DESC');
                $itemModel->take(5);
                $items = $itemModel->get();
                $need = 5 - $items->count();

                if ($items->count() < 5) {
                    $itemModel = (new \OlaHub\UserPortal\Models\CatalogItem)->newQuery();
                    $itemModel->where('id', '!=', $itemID);
                    $itemModel->whereHas('quantityData', function ($q) {
                        $q->where('quantity', '>', 0);
                    })->where(function ($query) {
                        $query->whereNull('parent_item_id');
                        $query->orWhere('parent_item_id', '0');
                    })->where('is_published', '=', 1);
                    $itemModel->orderBy('total_views', 'DESC');
                    $itemModel->orderBy('name', 'ASC');
                    $itemModel->take($need);
                    $items2 = $itemModel->get();
                    if ($items2->count() < 1) {
                        throw new NotAcceptableHttpException(404);
                    }
                    foreach ($items2 as $item) {
                        $items[] = $item;
                    }
                }
            } else {
                $itemModel = (new \OlaHub\UserPortal\Models\CatalogItem)->newQuery();
                $itemModel->where('id', '!=', $itemID);
                $itemModel->whereHas('quantityData', function ($q) {
                    $q->where('quantity', '>', 0);
                })->where(function ($query) {
                    $query->whereNull('parent_item_id');
                    $query->orWhere('parent_item_id', '0');
                });
                $itemModel->where('is_published', '=', 1);
                $itemModel->orderBy('total_views', 'DESC');
                $itemModel->orderBy('name', 'ASC');
                $itemModel->take(5);
                $items = $itemModel->get();
                if ($items->count() < 1) {
                    throw new NotAcceptableHttpException(404);
                }
            }
        } else {
            $itemModel = (new \OlaHub\UserPortal\Models\CatalogItem)->newQuery();
            $itemModel->where('id', '!=', $itemID);
            $itemModel->whereHas('quantityData', function ($q) {
                $q->where('quantity', '>', 0);
            })->where(function ($query) {
                $query->whereNull('parent_item_id');
                $query->orWhere('parent_item_id', '0');
            });
            $itemModel->where('is_published', '=', 1);
            $itemModel->orderBy('total_views', 'DESC');
            $itemModel->orderBy('name', 'ASC');
            $itemModel->take(5);
            $items = $itemModel->get();
            if ($items->count() < 1) {
                throw new NotAcceptableHttpException(404);
            }
        }

        $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($items, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler');
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }
    public function getItemFiltersClassessData($all = false)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getItemFiltersClassessData"]);

        $this->ItemsCriatria();
        $itemsIDs = $this->itemsModel->pluck('id');
        $classesMainModel = (new \OlaHub\UserPortal\Models\Classification)->newQuery();
        $classesMainModel->whereHas('itemsMainData', function ($q) use ($itemsIDs) {
            $q->whereIn('id', $itemsIDs);
        })->groupBy('id');
        if ($all) {
            $classes = $classesMainModel->get();
        } else {
            $classes = $classesMainModel->paginate(5);
        }


        if ($classes->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        if ($all) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($classes, '\OlaHub\UserPortal\ResponseHandlers\ClassificationResponseHandler');
        } else {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($classes, '\OlaHub\UserPortal\ResponseHandlers\ClassificationFilterResponseHandler');
        }

        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getItemFiltersBrandData($all = false)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getItemFiltersBrandData"]);

        $brands = [];
        $designers = [];

        if (empty($this->requestFilter['brandSlug']) && empty($this->requestFilter['designers']) ) {
            // for stores
            $this->itemsType = "store";
            $this->ItemsCriatria();

            $itemsIDs = $this->itemsModel->pluck('id');
            $brandModel = (new \OlaHub\UserPortal\Models\Brand)->newQuery();
            $brandModel->whereHas('itemsMainData', function ($q) use ($itemsIDs) {
                $q->whereIn('id', $itemsIDs);
            });
            if ($all) {
                $brands = $brandModel->get();
            } else {
                $brands = $brandModel->paginate(5);
            }
        }
        if (empty($this->requestFilter['designerSlug']) && empty($this->requestFilter['brands'])) {
            // for designers
            $this->itemsType = "designer";
            $this->ItemsCriatria();

            $itemsIDs = $this->itemsModel->pluck('id');
            $designersModel = (new \OlaHub\UserPortal\Models\Designer())->newQuery();
            $designersModel->whereHas('itemsMainData', function ($q) use ($itemsIDs) {
                $q->whereIn('id', $itemsIDs);
            });
            if ($all) {
                $designers = $designersModel->get();
            } else {
                $designers = $designersModel->paginate(5);
            }
        }

        $return["brands"] = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($brands, '\OlaHub\UserPortal\ResponseHandlers\BrandsResponseHandler')["data"];
        $return["designers"] = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($designers, '\OlaHub\UserPortal\ResponseHandlers\DesignerResponseHandler')["data"];
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getItemFiltersOccasionData($all = false)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getItemFiltersOccasionData"]);

        $this->ItemsCriatria();
        $itemsIDs = $this->itemsModel->pluck('id');
        $occasionModel = (new \OlaHub\UserPortal\Models\Occasion)->newQuery();
        $occasionModel->whereHas('occasionItemsData', function ($q) use ($itemsIDs) {
            $q->whereIn('catalog_items.parent_item_id', $itemsIDs);
            $q->groupBy('occassion_id');
        });
        if ($all) {
            $occasions = $occasionModel->get();
        } else {
            $occasions = $occasionModel->paginate(5);
        }


        if ($occasions->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($occasions, '\OlaHub\UserPortal\ResponseHandlers\OccasionsResponseHandler');
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getItemFiltersCatsData($all = false)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getItemFiltersCatsData"]);

        $this->ItemsCriatria();
        $itemsIDs = $this->itemsModel->pluck('id');
        $categoryModel = (new \OlaHub\UserPortal\Models\ItemCategory)->newQuery();
        $categoryModel->where(function ($wherQ) use ($itemsIDs) {
            $wherQ->where(function ($ww) {
                $ww->whereNull('parent_id')
                    ->orWhere('parent_id', '0');
            });
            $wherQ->whereHas('itemsMainData', function ($q) use ($itemsIDs) {
                $q->whereIn('id', $itemsIDs);
            });
        });
        $categoryModel->orWhere(function ($wherQ) use ($itemsIDs) {
            $wherQ->whereHas('childsData', function ($childQ) use ($itemsIDs) {
                $childQ->whereHas('itemsMainData', function ($q) use ($itemsIDs) {
                    $q->whereIn('id', $itemsIDs);
                });
            });
        });
        $categoryModel->groupBy('id');
        if ($all) {
            $categories = $categoryModel->get();
        } else {
            $categories = $categoryModel->paginate(5);
        }
        if ($categories->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return = \OlaHub\UserPortal\Models\ItemCategory::setReturnResponse($categories, $itemsIDs);
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getItemFiltersAttrsData($all = false)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getItemFiltersCatsData"]);
        $attributes = NULL;
        $target = NULL;
        $storeAttrs = [];
        $designersAttrs = [];
        $otherAttrs = [];
        ///////

        if (!empty($this->requestFilter['brandSlug']) || !empty($this->requestFilter['brands']))
            $target = "brands";
        if (!empty($this->requestFilter['designerSlug']) || !empty($this->requestFilter['designers']))
            $target = "designers";

        if (!$target || $target == 'brands') {
            $this->itemsType = "store";
            $this->ItemsCriatria();
            $storeItemsIDs = $this->itemsModel->pluck('id');

            $attributeModel = (new \OlaHub\UserPortal\Models\Attribute)->newQuery();
            if(!empty($this->requestFilter['attributesParent'])){
                $attributeModel->whereHas('valuesData', function ($values) use ($storeItemsIDs) {
                    $values->whereHas('valueItemsData', function ($q) use ($storeItemsIDs) {
                        $q->whereIn('item_id', $storeItemsIDs);
                        $q->orWhereIn('parent_item_id', $storeItemsIDs);
                    })->whereNotIn('product_attribute_id', $this->requestFilter['attributesParent']);
                });
            }
           

            $attributes = $attributeModel->groupBy('id')->get();
            if ($storeItemsIDs) {
                foreach ($attributes as $attribute) {
                    $childs = $attribute->valuesData()->whereHas("valueItemsData", function ($q) use ($storeItemsIDs) {
                        $q->whereIn('item_id', $storeItemsIDs);
                    })->groupBy('id')->get();
                    foreach ($childs as $child)
                        $otherAttrs[] = $child->id;
                }
            }
            $storeAttrs = \OlaHub\UserPortal\Models\Attribute::setReturnResponse($attributes, $storeItemsIDs)["data"];
        }
        if (!$target || $target == 'designers') {
            $this->itemsType = "designer";
            $this->ItemsCriatria();
            $designerItemsIDs = $this->itemsModel->pluck('id');

            $attributeModel = (new \OlaHub\UserPortal\Models\Attribute)->newQuery();
            $attributeModel->whereHas('valuesData', function ($values) use ($designerItemsIDs, $otherAttrs) {
                $values->whereHas('valueDesignerData', function ($q) use ($designerItemsIDs) {
                    $q->whereIn('item_id', $designerItemsIDs);
                    $q->orWhereIn('parent_item_id', $designerItemsIDs);
                })->whereNotIn('product_attribute_id', $this->requestFilter['attributesParent']);
            });
            $attributes = $attributeModel->groupBy('id')->get();
            $designersAttrs = \OlaHub\UserPortal\Models\Attribute::setReturnResponse($attributes, $designerItemsIDs, false, "valueDesignerData", $otherAttrs)['data'];
        }
        $return["data"] = $this->mergeAttributes(array_merge($storeAttrs,  $designersAttrs));

        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getSelectedAttributes($all = false)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getItemFiltersCatsData"]);

        $attributeModel = (new \OlaHub\UserPortal\Models\Attribute)->newQuery();
        $attributeModel->whereIn('id', $this->requestFilter['attributesParent']);
        $attributes = $attributeModel->groupBy('id')->get();
        if ($attributes->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }

        $return['data'] = [];
        foreach ($attributes as $attribute) {
            $attrData = [
                "valueID" => isset($attribute->id) ? $attribute->id : 0,
                "valueName" => isset($attribute->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($attribute, 'name') : NULL,
                "valueColorStyle" => isset($attribute->is_color_style) ? $attribute->is_color_style : 0,
                "valueSizeStyle" => isset($attribute->is_size_style) ? $attribute->is_size_style : 0,
            ];

            $attrData['childsData'] = [];
            $childs = $attribute->valuesData()->groupBy('id')->get();
            foreach ($childs as $child) {
                if (in_array($child->id, $this->requestFilter['attributesChildsId'])) {
                    $attrData['childsData'][] = [
                        "valueID" => isset($child->id) ? $child->id : 0,
                        "valueName" => isset($child->attribute_value) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($child, 'attribute_value') : NULL,
                        "valueHexColor" => isset($child->color_hex_code) ? $child->color_hex_code : NULL,
                    ];
                }
            }
            $return['data'][] = $attrData;
        }

        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getOfferItemsPage()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getOfferItemsPage"]);

        $target = NULL;
        $catalogItems = [];
        $designerItems = [];
        $allItems = [];
        $meta = NULL;

        if (!empty($this->requestFilter['brands']))
            $target = "brands";
        if (!empty($this->requestFilter['designers']))
            $target = "designers";

        if (!$target || $target == 'brands') {
            $this->offerItemsCriatria();
            $catalogItems = $this->itemsModel->paginate(20);
            $catalogItems = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($catalogItems, '\OlaHub\UserPortal\ResponseHandlers\ItemsListResponseHandler');
        }
        if (!$target || $target == 'designers') {
            $this->offerItemsCriatria(true, true, "designer");
            $designerItems = $this->itemsModel->paginate(20);
            $designerItems = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($designerItems, '\OlaHub\UserPortal\ResponseHandlers\DesginerItemsHandler');
        }

        $meta = (count($catalogItems) ? $catalogItems["meta"] : $designerItems["meta"]);
        if (count($catalogItems))
            $catalogItems = $catalogItems["data"];
        if (count($designerItems)) {
            if ($designerItems["meta"]["pagination"]["total_pages"] > $meta["pagination"]["total_pages"])
                $meta = $designerItems["meta"];
            $designerItems = $designerItems["data"];
        }

        $allItems = array_merge($catalogItems, $designerItems);
        // if ($items->count() < 1) {
        //     throw new NotAcceptableHttpException(404);
        // }
        $return['meta'] = $meta;
        $return['data'] = $allItems;
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getOfferItemsPageAttribute($all = false)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getOfferItemsPageAttribute"]);

        $this->offerItemsCriatria(false);
        $itemsIDs = $this->itemsModel->pluck('catalog_items.id');
        $attributeModel = (new \OlaHub\UserPortal\Models\Attribute)->newQuery();
        $attributeModel->whereHas('valuesData', function ($values) use ($itemsIDs) {
            $values->whereHas('valueItemsData', function ($q) use ($itemsIDs) {
                $q->whereIn('item_id', $itemsIDs);
            })->whereNotIn('product_attribute_id', $this->requestFilter['attributesParent']);
        });
        $attributes = $attributeModel->groupBy('id')->get();
        if ($attributes->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }

        $return = \OlaHub\UserPortal\Models\Attribute::setReturnResponse($attributes, $itemsIDs, $this->first);
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getOfferItemsPageCategories($all = false)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getOfferItemsPageCategories"]);


        $this->offerItemsCriatria();
        $itemsIDs = $this->itemsModel->pluck('id');
        $categoryModel = (new \OlaHub\UserPortal\Models\ItemCategory)->newQuery();
        $categoryModel->where(function ($wherQ) use ($itemsIDs) {
            $wherQ->where(function ($ww) {
                $ww->whereNull('parent_id')
                    ->orWhere('parent_id', '0');
            });
            $wherQ->whereHas('itemsMainData', function ($q) use ($itemsIDs) {
                $q->whereIn('id', $itemsIDs);
            });
        });
        $categoryModel->orWhere(function ($wherQ) use ($itemsIDs) {
            $wherQ->whereHas('childsData', function ($childQ) use ($itemsIDs) {
                $childQ->whereHas('itemsMainData', function ($q) use ($itemsIDs) {
                    $q->whereIn('id', $itemsIDs);
                });
            });
        });
        $categoryModel->groupBy('id');
        if ($all) {
            $categories = $categoryModel->get();
        } else {
            $categories = $categoryModel->paginate(5);
        }
        if ($categories->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return = \OlaHub\UserPortal\Models\ItemCategory::setReturnResponse($categories, $itemsIDs);
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    private function offerItemsCriatria($any = true, $same = true, $type = "catalog")
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "offerItemsCriatria"]);

        $this->itemsModel = (new CatalogItem)->newQuery();
        if ($type == "designer")
            $this->itemsModel = (new DesignerItems)->newQuery();
        $this->itemsModel->selectRaw($type . '_items.*, ((discounted_price / price) * 100) as discount_perc');
        if (isset($this->requestFilter['priceFrom']) && strlen($this->requestFilter['priceFrom']) > 0) {
            $this->itemsModel->where(function ($query) {
                $query->Where(function ($q) {
                    $q->whereNotNull('discounted_price_start_date');
                    $q->whereNotNull('discounted_price_end_date');
                    $q->where('discounted_price_start_date', '<=', date('Y-m-d') . " 00:00:01");
                    $q->where('discounted_price_end_date', '>=', date('Y-m-d') . " 23:59:59");
                    $q->where('discounted_price', ">=", (float) $this->requestFilter['priceFrom']);
                });
            });
        }

        if (isset($this->requestFilter['priceTo']) && strlen($this->requestFilter['priceTo']) > 0) {
            $this->itemsModel->where(function ($query) {
                $query->Where(function ($q) {
                    $q->whereNotNull('discounted_price_start_date');
                    $q->whereNotNull('discounted_price_end_date');
                    $q->where('discounted_price_start_date', '<=', date('Y-m-d') . " 00:00:01");
                    $q->where('discounted_price_end_date', '>=', date('Y-m-d') . " 23:59:59");
                    $q->where('discounted_price', "<=", (float) $this->requestFilter['priceTo']);
                });
            });
        }
        $this->itemsModel->where(function ($query) {
            $query->whereNotNull('discounted_price_start_date');
            $query->whereNotNull('discounted_price_end_date');
            $query->where('discounted_price_start_date', '<=', date('Y-m-d') . " 00:00:01");
            $query->where('discounted_price_end_date', '>=', date('Y-m-d') . " 23:59:59");
        });

        if (count($this->requestFilter) > 0) {
            unset($this->requestFilter['all']);
            $filters = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingRequestFilter($this->requestFilter, CatalogItem::$columnsMaping);
            $this->setFilterMainData($filters, $same);
            if (isset($this->requestFilter['attributes']) && count($this->requestFilter['attributes']) > 0) {
                $attributes = [];
                foreach ($this->requestFilter['attributes'] as $one) {
                    $attrData = \OlaHub\UserPortal\Models\AttrValue::find($one);
                    if ($attrData) {
                        $attributes[$attrData->product_attribute_id][] = $one;
                    }
                }

                foreach ($attributes as $key => $values) {
                    $this->itemsModel->join($type . "_item_attribute_values as ciav$key", "ciav$key.item_id", "=", $type . "_items.id");
                    $this->itemsModel->whereIn("ciav$key." . ($type == "catalog" ?  "item_attribute_value_id" :  "value_id"), $values);
                }
            }
            $this->setFilterRelationData($filters, $same);
        }
        $this->itemsModel->groupBy($type . '_items.id');
        if ($any) {
            $this->itemsModel->where(function ($query) use ($type) {
                $query->whereNull($type . '_items.parent_item_id');
                $query->orWhere($type . '_items.parent_item_id', '0');
            });
        }
        $this->sortItems();
    }

    /*
     * Helper functions
     */

    private function ItemsCriatria($any = true, $same = true)
    {
        //heba
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "ItemsCriatria"]);

        $this->itemsModel = $this->itemsType == "store" ?
            (new CatalogItem)->newQuery() : (new DesignerItems)->newQuery();
        $tableName = $this->itemsType == "store" ? "catalog_items" : "designer_items";

        if (isset($this->requestFilter['priceFrom']) && strlen($this->requestFilter['priceFrom']) > 0) {
            $this->itemsModel->where(function ($query) {
                $query->where(function ($q) {
                    $q->Where('discounted_price_end_date', '<', date('Y-m-d') . " 23:59:59");
                    $q->where('price', ">=", (float) $this->requestFilter['priceFrom']);
                })->orWhere(function ($q) {
                    $q->WhereNull('discounted_price_end_date');
                    $q->where('price', ">=", (float) $this->requestFilter['priceFrom']);
                })->orWhere(function ($q) {
                    $q->whereNotNull('discounted_price_start_date');
                    $q->whereNotNull('discounted_price_end_date');
                    $q->where('discounted_price_start_date', '<=', date('Y-m-d') . " 00:00:01");
                    $q->where('discounted_price_end_date', '>=', date('Y-m-d') . " 23:59:59");
                    $q->where('discounted_price', ">=", (float) $this->requestFilter['priceFrom']);
                });
            });
        }

        if (isset($this->requestFilter['priceTo']) && strlen($this->requestFilter['priceTo']) > 0) {
            $this->itemsModel->where(function ($query) {
                $query->where(function ($q) {
                    $q->where(function ($qWhere) {
                        $qWhere->Where('discounted_price_end_date', '<', date('Y-m-d') . " 23:59:59");
                        $qWhere->orWhereNull('discounted_price_end_date');
                    });
                    $q->where('price', "<=", (float) $this->requestFilter['priceTo']);
                })->orWhere(function ($q) {
                    $q->whereNotNull('discounted_price_start_date');
                    $q->whereNotNull('discounted_price_end_date');
                    $q->where('discounted_price_start_date', '<=', date('Y-m-d') . " 00:00:01");
                    $q->where('discounted_price_end_date', '>=', date('Y-m-d') . " 23:59:59");
                    $q->where('discounted_price', "<=", (float) $this->requestFilter['priceTo']);
                });
            });
        }

        if (isset($this->requestFilter['offerOnly']) && $this->requestFilter['offerOnly']) {
            $this->itemsModel->where(function ($query) {
                $query->whereNotNull('discounted_price_start_date');
                $query->whereNotNull('discounted_price_end_date');
                $query->where('discounted_price_start_date', '<=', date('Y-m-d') . " 00:00:01");
                $query->where('discounted_price_end_date', '>=', date('Y-m-d') . " 23:59:59");
            });
        }

        if (@count($this->requestFilter) > 0 && ($this->force == true || (isset($this->requestFilter['all']) && (string) $this->requestFilter['all'] == "0"))) {
            // unset($this->requestFilter['all']);
            if (isset($this->requestFilter['attributes']) && count($this->requestFilter['attributes']) > 0) {
                $this->itemsModel->whereHas('valuesData', function ($query) {
                    $query->whereHas('valueMainData', function ($query1) {
                        $query1->whereIn('id', $this->requestFilter['attributes']);
                    });
                });
            }
            $filters = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingRequestFilter($this->requestFilter, CatalogItem::$columnsMaping);
            // var_dump($filters);
            foreach ($filters['main'] as $input => $value) {
                if (is_array($value) && count($value)) {
                    $this->itemsModel->whereIn($input, $value);
                } elseif (is_string($value) && strlen($value) > 0) {
                    $this->itemsModel->where($input, $value);
                }
            }

            foreach ($filters['relations'] as $model => $data) {
                if ($model == 'brand') {
                    $this->itemsModel->selectRaw("catalog_items.*, merchant_stors.name as brand_name, SUM(catalog_item_stors.quantity) as qu")
                        ->leftJoin("catalog_item_stors", "catalog_item_stors.item_id", "=", "catalog_items.id");
                    $this->itemsModel->leftJoin("merchant_stors", "merchant_stors.id", "=", "catalog_items.store_id");
                    foreach ($data as $input => $value) {
                        $this->itemsModel->where("merchant_stors." . $input, $value);
                    }
                } elseif ($model == "designer") {
                    $this->itemsModel->selectRaw("designer_items.*")
                        ->leftJoin("designers", "designers.id", "=", "designer_items.designer_id");
                    foreach ($data as $input => $value) {
                        $this->itemsModel->where("designers." . $input, $value);
                    }
                } else {
                    $this->itemsModel->whereHas($model, function ($q) use ($data) {
                        foreach ($data as $input => $value) {
                            if (is_array($value) && count($value)) {
                                $q->whereIn($input, $value);
                            } elseif (is_string($value) && strlen($value) > 0) {
                                $q->where($input, $value);
                            }
                        }
                    });
                }
            }
        }

        $this->itemsModel->groupBy($tableName . '.id');
        if ($any && !(isset($this->requestFilter['attributes']) && count($this->requestFilter['attributes']) > 0)) {
            $this->itemsModel->where(function ($query) use ($tableName) {
                $query->whereNull($tableName . '.parent_item_id');
                $query->orWhere($tableName . '.parent_item_id', '0');
            });
            $this->first = true;
        }
    }

    private function oneItemsCriatria($type = "catalog")
    {
        if (@count($this->requestFilter) > 0 && ($this->force == true || (isset($this->requestFilter['all']) && (string) $this->requestFilter['all'] == "0"))) {
            unset($this->requestFilter['all']);
            $filters = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingRequestFilter($this->requestFilter, CatalogItem::$columnsMaping);
            $this->setFilterMainData($filters, true);
            if (isset($this->requestFilter['attributes']) && count($this->requestFilter['attributes']) > 0) {
                $attributes = [];
                foreach ($this->requestFilter['attributes'] as $one) {
                    $attrData = \OlaHub\UserPortal\Models\AttrValue::find($one);
                    if ($attrData) {
                        $attributes[$attrData->product_attribute_id][] = $one;
                    }
                }

                foreach ($attributes as $key => $values) {
                    $this->itemsModel->join($type . "_item_attribute_values as ciav$key", "ciav$key.item_id", "=", $type . "_items.id");
                    $this->itemsModel->whereIn("ciav$key." . ($type == "catalog" ?  "item_attribute_value_id" :  "value_id"), $values);
                }

                $this->itemsModel->select($type . "_items.*");
            }
            $this->setFilterRelationData($filters, true);
        }
        $this->itemsModel->groupBy($type . '_items.id');
    }

    private function sortItems($column = 'created_at', $type = 'DESC')
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "sortItems"]);

        if ($this->requestSort) {
            $order = explode('-', $this->requestSort);
            if (count($order) == 2 && isset($order[0]) && isset($order[1])) {
                switch ($order[0]) {
                    case 'create':
                        $column = 'created_at';
                        break;
                    case 'name':
                        $column = 'name';
                        break;
                    case 'price':
                        if (isset($this->requestFilter['offerOnly']) && $this->requestFilter['offerOnly']) {
                            $column = 'discounted_price';
                        } else {
                            $column = 'price';
                        }
                        break;
                    case 'discountedPrice':
                        $column = 'discounted_price';
                        break;
                }
                if (in_array($order[1], ['asc', 'desc'])) {
                    $type = strtoupper($order[1]);
                }
            }
        }
        $this->itemsModel->orderBy($column, $type);
    }

    private function setFilterMainData($filters, $same = true)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "setFilterMainData"]);

        foreach ($filters['main'] as $input => $value) {
            if (is_array($value) && count($value)) {
                $same ? $this->itemsModel->whereIn($input, $value) : $this->itemsModel->whereNotIn($input, $value);
            } elseif (is_string($value) && strlen($value) > 0) {
                $same ? $this->itemsModel->where($input, $value) : $this->itemsModel->where($input, '!=', $value);
            }
        }
    }

    private function setFilterRelationData($filters, $same = true)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "setFilterRelationData"]);

        foreach ($filters['relations'] as $model => $data) {
            if ($model == 'brand') {
                $this->itemsModel->selectRaw("catalog_items.*, merchant_stors.name as brand_name, SUM(catalog_item_stors.quantity) as qu")
                    ->leftJoin("catalog_item_stors", "catalog_item_stors.item_id", "=", "catalog_items.id");
                $this->itemsModel->leftJoin("merchant_stors", "merchant_stors.id", "=", "catalog_items.store_id");
                foreach ($data as $input => $value) {
                    $this->itemsModel->where("merchant_stors." . $input, $value);
                }
            } elseif ($model == "designer") {
                $this->itemsModel->selectRaw("designer_items.*")
                    ->leftJoin("designers", "designers.id", "=", "designer_items.designer_id");
                foreach ($data as $input => $value) {
                    $this->itemsModel->where("designers." . $input, $value);
                }
            } else {
                $this->itemsModel->whereHas($model, function ($q) use ($data) {
                    foreach ($data as $input => $value) {
                        if (is_array($value) && count($value)) {
                            $q->whereIn($input, $value);
                        } elseif (is_string($value) && strlen($value) > 0) {
                            $q->where($input, $value);
                        }
                    }
                });
            }
        }
    }

    public function uploadCustomImage()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "uploadCustomImage"]);

        $this->requestData = isset($this->uploadImage) ? $this->uploadImage : [];
        if (isset($this->requestData['customeImage']) && $this->requestData['customeImage']) {
            $uploadResult = \OlaHub\UserPortal\Helpers\GeneralHelper::uploader($this->requestData['customeImage'], DEFAULT_IMAGES_PATH . "customeImage/", "customeImage/", false);

            if (array_key_exists('path', $uploadResult)) {
                $return = [];
                $return['path'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($uploadResult['path']);
                $return['status'] = TRUE;
                $return['code'] = 200;
                $log->setLogSessionData(['response' => $return]);
                $log->saveLogSessionData();
                return response($return, 200);
            } else {
                $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
                $logHelper->setLog($this->requestData, $uploadResult, 'joinPublicGroup', $this->userAgent);
                response($uploadResult, 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }
}
