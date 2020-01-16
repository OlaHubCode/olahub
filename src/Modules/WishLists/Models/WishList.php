<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class WishList extends Model {

    private $return;
    private $data;
    private $item;

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);

        static::addGlobalScope('currentUser', function ($query) {
            $query->where('user_id', app('session')->get('tempID'));
        });

        static::addGlobalScope('wishlistCountry', function ($query) {
            $query->whereHas('itemsMainData', function ($q) {
                $q->whereHas('merchant', function ($merchantQ) {
                    $merchantQ->where('country_id', app('session')->get('def_country')->id);
                });
            });
        });

        /* static::addGlobalScope('hasItem', function ($query) {
          $query->has('itemsMainData');
          }); */
    }

    protected $table = 'liked_items';
    static $columnsMaping = [
        'itemID' => [
            'column' => 'item_id',
            'type' => '',
            'relation' => false,
            'validation' => 'required|numeric'
        ],
        'occasionValue' => [
            'column' => 'occasion_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'array|max:4'
        ],
        'wishlistType' => [
            'column' => 'is_public',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|in:0,1'
        ],
    ];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('wishList', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('type', 'wish');
        });

        static::saving(function ($query) {
            $query->type = 'wish';
        });
    }

    public function itemsMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem', 'item_id');
    }

    public function setWishlistData($wishlist) {
        $this->return = [];
        $occassions = [];
        foreach ($wishlist as $item) {
            $this->data = $item;
            switch ($item->item_type) {
                case "store":
                    $this->item = $this->data->itemsMainData;
                    if ($this->item) {
                        $this->setOccasion($occassions, $item);
                        $occassions[] = $item->occasion_id;
                        $this->setItemMainData();
                        $this->setItemImageData();
                        $this->setPriceData();
                        $this->setItemOwnerData();
                        $this->setAddData($this->item->id);
                    }

                    break;
                case "designer":
                    $this->item = false;
                    $itemMain = \OlaHub\UserPortal\Models\DesginerItems::whereIn("item_ids", [(int) $item->item_id, (string) $item->item_id])->first();
                    if ($itemMain) {
                        $this->item = false;
                        if (isset($itemMain->items) && count($itemMain->items) > 0) {
                            foreach ($itemMain->items as $oneItem) {
                                if ($oneItem["item_id"] == $itemMain->item_id) {
                                    $this->item = (object) $oneItem;
                                }
                            }
                        }
                        if (!$this->item) {
                            $this->item = $itemMain;
                        }
                        $this->setOccasion($occassions, $item);
                        $occassions[] = $item->occasion_id;
                        $this->getDesignerItem($itemMain);
                        $this->setAddData($this->item->item_id);
                    }
                    break;
            }
        }

        return $this->return;
    }

    private function setOccasion($occassions,$item) {
        if (!in_array($item->occasion_id, $occassions)) {
            if ($item->occasion_id == 0) {
                $this->return[$item->occasion_id] = [
                    "occasionId" => 0,
                    "occasionName" => "unCategoriezed",
                    "occasionSlug" => false,
                    "occasionImage" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                    "items" => []
                ];
            } else {
                $occassion = \OlaHub\UserPortal\Models\Occasion::withoutGlobalScope("country")->where("id", $item->occasion_id)->first();
                $this->return[$item->occasion_id] = [
                    "occasionId" => $item->occasion_id,
                    "occasionName" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occassion, "name"),
                    "occasionSlug" => isset($occassion->occasion_slug) ? $occassion->occasion_slug : NULL,
                    "occasionImage" => isset($occassion->logo_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($occassion->logo_ref) : NULL,
                    "items" => []
                ];
            }
        }
    }

    private function getDesignerItem($itemMain) {
        $itemPrice = \OlaHub\UserPortal\Models\DesginerItems::checkPrice($this->item);
        $itemName = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($itemMain, 'item_title');
        $itemDescription = str_limit(strip_tags(\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($itemMain, 'item_description')), 350, '.....');
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productID"] = isset($this->item->item_id) ? $this->item->item_id : 0;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->item, 'item_slug', \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->item, 'item_title'));
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productName"] = $itemName;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productType"] = "designer";
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productDescription"] = $itemDescription;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productInStock"] = isset($this->item->item_stock) && $this->item->item_stock ? $this->item->item_stock : "1";
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["wishlistId"] = $this->data->id ? $this->data->id : 0;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productPrice"] = $itemPrice['productPrice'];
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productDiscountedPrice"] = $itemPrice['productDiscountedPrice'];
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productHasDiscount"] = $itemPrice['productHasDiscount'];
        $this->setDesignerItemImageData($this->item);
        $this->setDesignerItemOwnerData($itemMain);
//        $itemOwner = $this->setDesignerItemOwnerData($itemMain);
//        $this->return['products'][] = array(
//            "productID" => isset($item->item_id) ? $item->item_id : 0,
//            "productValue" => isset($item->_id) ? $item->_id : 0,
//            "productType" => "designer",
//            "productSlug" => ,
//            "productName" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($itemMain, 'item_title'),
//            "productDescription" => 
//            "productInStock" => isset($item->item_stock) && $item->item_stock ? $item->item_stock : "1",
//            "productPrice" => ,
//            "productDiscountedPrice" => ,
//            "productHasDiscount" => ,
//            "productQuantity" => $cartItem->quantity,
//            "productTotalPrice" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice((double) \OlaHub\UserPortal\Models\DesginerItems::checkPrice($item, true, false) * $cartItem->quantity),
//            "productImage" => $this->setDesignerItemImageData($item),
//            "productOwner" => $itemOwner['productOwner'],
//            "productOwnerName" => $itemOwner['productOwnerName'],
//            "productOwnerSlug" => $itemOwner['productOwnerSlug'],
//            "productselectedAttributes" => $this->setDesignerItemSelectedAttrData($item),
//            "productCustomeItem" => $this->setItemCustomData($cartItem->customize_data),
//        );
    }

    private function setDesignerItemImageData($item) {
        $images = isset($item->item_image) ? $item->item_image : (isset($item->item_images) ? $item->item_images : false);
        if ($images && is_array($images) && count($images) > 0) {
            $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]);
        } else {
            $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setDesignerItemOwnerData($item) {
        $designer = \OlaHub\UserPortal\Models\Designer::find($item->designer_id);
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productOwner"] = isset($designer->id) ? $designer->id : NULL;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productOwnerName"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($designer, 'designer_name');
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productOwnerSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($designer, 'designer_slug', $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productOwnerName"]);
    }

    private function setItemMainData() {
        $itemName = isset($this->item->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->item, 'name') : NULL;
        $itemDescription = isset($this->item->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->item, 'description') : NULL;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productID"] = isset($this->item->id) ? $this->item->id : 0;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->item, 'item_slug', $itemName);
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productName"] = $itemName;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productType"] = "store";
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productDescription"] = str_limit(strip_tags($itemDescription), 350, '.....');
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productInStock"] = \OlaHub\UserPortal\Models\CatalogItem::checkStock($this->item);
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["wishlistId"] = $this->data->id ? $this->data->id : 0;
    }

    private function setItemImageData() {
        $images = isset($this->item->images) ? $this->item->images : [];
        if (count($images) > 0 && $images->count() > 0) {
            $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setPriceData() {
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productPrice"] = isset($this->item->price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->item->price) : 0;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productDiscountedPrice"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice(0);
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productHasDiscount"] = false;
        if ($this->item->has_discount && strtotime($this->item->discounted_price_start_date) <= time() && strtotime($this->item->discounted_price_end_date) >= time()) {
            $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productDiscountedPrice"] = isset($this->item->discounted_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->item->discounted_price) : 0;
            $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productHasDiscount"] = true;
        }
    }

    private function setItemOwnerData() {
        $merchant = $this->item->merchant;
        $ownerName = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($merchant, 'company_legal_name');
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productOwner"] = isset($merchant->id) ? $merchant->id : NULL;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productOwnerName"] = $ownerName;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productOwnerSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($merchant, 'merchant_slug', $ownerName);
    }

    private function setAddData($itemID) {
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productWishlisted'] = '0';
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productLiked'] = '0';
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productInCart'] = '0';

        //wishlist
        if (\OlaHub\UserPortal\Models\WishList::where('item_id', $itemID)->count() > 0) {
            $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productWishlisted'] = '1';
        }

        //like
        if (\OlaHub\UserPortal\Models\LikedItems::where('item_id', $itemID)->count() > 0) {
            $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productLiked'] = '1';
        }

        //Cart
        if (\OlaHub\UserPortal\Models\Cart::whereHas('cartDetails', function ($q) use($itemID) {
                    $q->where('item_id', $itemID);
                })->count() > 0) {
            $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productInCart'] = '1';
        }
    }

}
