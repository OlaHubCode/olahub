<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class WishList extends Model
{

    private $return;
    private $data;
    private $item;

    public function __construct(array $attributes = array())
    {
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

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('wishList', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('type', 'wish');
        });

        static::saving(function ($query) {
            $query->type = 'wish';
        });
    }

    public function itemsMainData()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem', 'item_id');
    }

    public function designersMainData()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\DesignerItems', 'item_id');
    }

    public function setWishlistData($wishlist)
    {
        $this->return = [];
        $occassions = [];
        foreach ($wishlist as $item) {
            $this->data = $item;
            $this->setOccasion($occassions, $item);
            $occassions[] = $item->occasion_id;
            switch ($item->item_type) {
                case "store":
                    $this->item = $this->data->itemsMainData;
                    if ($this->item) {
                        $this->setPriceData();
                        $this->setItemOwnerData();
                    }
                    break;
                case "designer":
                    $this->item = $this->data->designersMainData;
                    if ($this->item) {
                        $this->setDesignerItemOwnerData();
                        $this->getDesignerItemPrice();
                    }
                    break;
            }
            $this->setItemMainData($item->item_type);
            $this->setItemImageData();
            $this->setAddData($this->item->id, $item->item_type);
        }

        return $this->return;
    }

    private function setOccasion($occassions, $item)
    {
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

    private function getDesignerItemPrice()
    {
        $itemPrice = \OlaHub\UserPortal\Models\DesignerItems::checkPrice($this->item);
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productPrice"] = $itemPrice['productPrice'];
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productDiscountedPrice"] = $itemPrice['productDiscountedPrice'];
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productHasDiscount"] = $itemPrice['productHasDiscount'];
    }

    private function setDesignerItemOwnerData()
    {
        $designer = $this->item->designer;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productOwner"] = isset($designer->id) ? $designer->id : NULL;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productOwnerName"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($designer, 'brand_name');
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productOwnerSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($designer, 'designer_slug', $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productOwnerName"]);
    }

    private function setItemMainData($type)
    {
        $itemName = isset($this->item->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->item, 'name') : NULL;
        $itemDescription = isset($this->item->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->item, 'description') : NULL;
        $stock = $type == 'store' ? \OlaHub\UserPortal\Models\CatalogItem::checkStock($this->item) : $this->item->item_stock;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productID"] = isset($this->item->id) ? $this->item->id : 0;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->item, 'item_slug', $itemName);
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productName"] = $itemName;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productType"] = $type;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productDescription"] = str_limit(strip_tags($itemDescription), 350, '.....');
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productInStock"] = $stock;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["wishlistId"] = $this->data->id ? $this->data->id : 0;
    }

    private function setItemImageData()
    {
        $images = isset($this->item->images) ? $this->item->images : [];
        if (count($images)) {
            $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setPriceData()
    {
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productPrice"] = isset($this->item->price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->item->price) : 0;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productDiscountedPrice"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice(0);
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productHasDiscount"] = false;
        if ($this->item->has_discount && strtotime($this->item->discounted_price_start_date) <= time() && strtotime($this->item->discounted_price_end_date) >= time()) {
            $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productDiscountedPrice"] = isset($this->item->discounted_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->item->discounted_price) : 0;
            $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productHasDiscount"] = true;
        }
    }

    private function setItemOwnerData()
    {
        $brand = $this->item->brand;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productOwner"] = $brand->id;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productOwnerName"] = $brand->name;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]["productOwnerSlug"] = @$brand->store_slug;
    }

    private function setAddData($itemID, $type)
    {
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productWishlisted'] = 0;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productLiked'] = 0;
        $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productInCart'] = 0;

        //wishlist
        // if (\OlaHub\UserPortal\Models\WishList::where('item_id', $itemID)->count() > 0) {
        //     $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productWishlisted'] = 1;
        // }

        //like
        // if (\OlaHub\UserPortal\Models\LikedItems::where('item_id', $itemID)->count() > 0) {
        //     $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productLiked'] = 1;
        // }

        //Cart
        if (\OlaHub\UserPortal\Models\Cart::whereHas('cartDetails', function ($q) use ($itemID, $type) {
            $q->where('item_id', $itemID);
            $q->where('item_type', $type);
        })->count() > 0) {
            $this->return[$this->data->occasion_id]["items"][$this->data->item_id . $this->data->item_type]['productInCart'] = 1;
        }
    }
}
