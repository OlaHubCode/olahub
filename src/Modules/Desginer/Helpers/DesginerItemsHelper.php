<?php

namespace OlaHub\UserPortal\Helpers;

use OlaHub\UserPortal\Models\ItemCategory;

use OlaHub\UserPortal\Helpers\CommonHelper;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\Cart;
use OlaHub\UserPortal\Models\CartItems;
use OlaHub\UserPortal\Models\CelebrationParticipantsModel;
use OlaHub\UserPortal\Models\Classification;
use OlaHub\UserPortal\Models\DesignerItems;
use OlaHub\UserPortal\Models\Following;
use OlaHub\UserPortal\Models\Interests;
use OlaHub\UserPortal\Models\LikedItems;
use OlaHub\UserPortal\Models\Occasion;

class DesginerItemsHelper extends CommonHelper
{

    private $return;
    private $request;

    function getOneItemData(DesignerItems $product, $slug, $requestFilter)
    {
        $this->return["productType"] = "designer";
        $this->return["productID"] = isset($product->id) ? $product->id : 0;
        $this->return["productSlug"] = isset($product->item_slug) ? $product->item_slug : null;
        $this->return["productName"] = isset($product->name) ? $product->name : null;
        $this->return["productDescription"] = isset($product->description) ? $product->description : null;
        $this->return["productInStock"] = isset($product->item_stock) ? $product->item_stock : 0;
        $this->return["productPrice"] = isset($product->price) ? \OlaHub\UserPortal\Helpers\CommonHelper::setDesignerPrice($product->price, true) : 0;
        $this->return["number"] = isset($product->id) ? $product->id : 0;
        $this->return["productShowLabel"] = true;
        $this->return["classifications"] = $this->setItemClassifications($product);
        $this->return["productLiked"] = 0;
        $this->return["productShared"] = 0;
        $this->return["productWishlisted"] = 0;
        $this->return["productIsNew"] = DesignerItems::checkIsNew($product);
        $customizeData = isset($product->customize_type) ? unserialize($product->customize_type) : 0;
        $this->return["productCustomizeType"] = !empty($customizeData) ? $customizeData['customization_details'] : 0;
        $this->return["productCustomizeLength"] = !empty($customizeData) ? $customizeData['character_length'] : 0;
        $this->return["productIsCustomized"] = $product->is_customized;
        $this->return["productIsMustCustom"] = $product->is_must_custom;

        $this->setItemOccasions($product);
        $this->setItemInterests($product);
        $this->setItemCategory($product);
        $this->setDesignerData($product);
        $this->setPriceData($product);
        $this->setImageData($product);
        $this->setRateData($product);
        $this->setShippingDatesData($product);
        $this->setCartData($product);
        $this->setItemSelectedAttrData($product);
        // $item = false;

        //like
        $liked = LikedItems::where('item_id', $product->id)
            ->where('item_type', 'designer')
            ->where('user_id', app('session')->get('tempID'))->first();
        if ($liked) {
            $this->return['productLiked'] = 1;
        }

        //wishlist
        if (\OlaHub\UserPortal\Models\WishList::where('item_id', $product->id)->where('item_type', 'designer')->count() > 0) {
            $this->return['productWishlisted'] = '1';
        }

        return $this->return;
    }

    private function setDesignerData($product)
    {
        $designer = $product->designer;
        $d = Following::where("target_id", $designer->id)->where("type", 2)
            ->where('user_id', app('session')->get('tempID'))->first();
        $this->return["productOwner"] = isset($designer->id) ? $designer->id : NULL;
        $this->return["productOwnerName"] = isset($designer->brand_name) ? $designer->brand_name : NULL;
        $this->return['productBrandLogo'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($designer->logo_ref);
        $this->return["productBrand"] = isset($designer) ? $designer->id : NULL;
        $this->return["productBrandName"] = isset($designer->brand_name) ? $designer->brand_name : NULL;
        $this->return["productOwnerSlug"] = isset($designer->designer_slug) ? $designer->designer_slug : NULL;
        $this->return["productBrandSlug"] = isset($designer->designer_slug) ? $designer->designer_slug : NULL;
        $this->return["productOwnerFollowers"] = isset($designer->id) ? Following::where("target_id", $designer->id)->where("type", 2)->count() : 0;
        $this->return['productOwnerFollowed'] = $d ? true : false;
        $this->return['followed'] = $d ? true : false;
    }

    private function setPriceData($product)
    {
        $return = DesignerItems::checkPrice($product);
        $this->return['productPrice'] = $return['productPrice'];
        $this->return['productDiscountedPrice'] = $return['productDiscountedPrice'];
        $this->return['productHasDiscount'] = $return['productHasDiscount'];
    }

    private function setImageData($product)
    {
        $this->return["productImages"] = [];
        if ($product->images) {
            foreach ($product->images as $image) {
                $this->return["productImages"][] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image->content_ref);
            }
        } else {
            $this->return["productImages"][] = "/img/no_image.png";
        }

        $this->return["productImage"] = isset($this->return["productImages"][0]) ? $this->return["productImages"][0] : "/img/no_image.png";
    }

    private function setRateData($product)
    {
        $this->return["productRate"] = 0;
    }

    private function setShippingDatesData($product)
    {
        $dateFrom = \OlaHub\UserPortal\Helpers\CommonHelper::checkHolidaysDatesNumber($product->min_shipping_days);
        $dateTo = \OlaHub\UserPortal\Helpers\CommonHelper::checkHolidaysDatesNumber($product->max_shipping_days);
        $this->return["shippingDateFrom"] = date("D d F, Y", strtotime("+$dateFrom Days"));
        if ($dateTo) {
            $this->return["shippingDateTo"] = date("D d F, Y", strtotime("+$dateTo Days"));
        }
        $exchange = $product->exchangePolicy;
        $this->return['exchangePolicy'] = isset($exchange->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($exchange, 'name') : null;
    }

    private function setCartData($product)
    {
        $this->request = Request::capture();
        $this->return['productInCart'] = 0;

        $itemID = $product->id;

        $headerCelebration = $this->request->headers->get("celebration") ? $this->request->headers->get("celebration") : "";
        if ($headerCelebration && app('session')->get('tempID')) {
            $existInCelebration = FALSE;
            $existCelebration = TRUE;
            $acceptParticipant = FALSE;
            $celebrationCart = Cart::withoutGlobalScope('countryUser')->where('celebration_id', $headerCelebration)->first();
            if ($celebrationCart) {
                $cartItem = CartItems::where('shopping_cart_id', $celebrationCart->id)->where('item_id', $itemID)->where("item_type", "designer")->first();
                if ($cartItem) {
                    $existInCelebration = TRUE;
                }
            } else {
                $existCelebration = FALSE;
            }
            $participant = CelebrationParticipantsModel::where('celebration_id', $headerCelebration)->where('is_approved', 1)->where('user_id', app('session')->get('tempID'))->first();
            if ($participant) {
                $acceptParticipant = TRUE;
            }
            $this->return["existCelebration"] = $existCelebration;
            $this->return["existInCelebration"] = $existInCelebration;
            $this->return["acceptParticipant"] = $acceptParticipant;
        }

        $cartCookie = $this->request->headers->get("cartCookie") ? json_decode($this->request->headers->get("cartCookie")) : [];

        if ($cartCookie && is_array($cartCookie) && count($cartCookie) > 0) {
            $id = $product->id;
            $itemsId = [];
            foreach ($cartCookie as $item) {
                array_push($itemsId, $item->id);
            }
            if (in_array($id, $itemsId)) {
                $this->return['productInCart'] = 1;
            }
        } else {
            if (Cart::whereHas('cartDetails', function ($q) use ($itemID) {
                $q->where('item_id', $itemID);
                $q->where("item_type", "designer");
            })->count() > 0) {
                $this->return['productInCart'] = 1;
            }
        }
    }

    private function setItemClassifications($product)
    {
        $class = [];
        if (isset($product->clasification_id) && $product->clasification_id) {
            $classification = Classification::where('id', $product->clasification_id)->first();
            if ($classification) {
                $class[] = [
                    "classificationId" => isset($classification->id) ? $classification->id : 0,
                    "classificationName" => isset($classification->name) ? CommonHelper::returnCurrentLangField($classification, "name") : null,
                    "classificationSlug" => isset($classification->class_slug) ? $classification->class_slug : null,
                ];
            }
        }
        return $class;
    }

    private function setItemCategory($product)
    {

        $category = ItemCategory::where('id', $product->category_id)->first();
        if (!$category) {
            return;
        }
        if ($category->parent_id > 0) {
            $this->return["subCategories"][] = [
                "subCategoryId" => isset($category->id) ? $category->id : 0,
                "subCategoryName" => isset($category->name) ? CommonHelper::returnCurrentLangField($category, 'name') : null,
                "subCategorySlug" => isset($category->category_slug) ? $category->category_slug : null,
            ];
            $parentCategory = ItemCategory::where('id', $category->parent_id)->first();
            $this->return["categories"][] = [
                "categoryId" => isset($parentCategory->id) ? $parentCategory->id : 0,
                "categoryName" => isset($parentCategory->name) ? CommonHelper::returnCurrentLangField($parentCategory, 'name') : null,
                "categorySlug" => isset($parentCategory->category_slug) ? $parentCategory->category_slug : null,
            ];
        } else {
            $this->return["categories"][] = [
                "categoryId" => isset($category->id) ? $category->id : 0,
                "categoryName" => isset($category->name) ? CommonHelper::returnCurrentLangField($category, 'name') : null,
                "categorySlug" => isset($category->category_slug) ? $category->parent_id : null,
            ];
        }
    }

    private function setItemInterests($product)
    {
        $interests = $product->interests;
        $inInts = [];
        foreach ($interests as $interest) {
            array_push($inInts, $interest->interest_id);
        }
        $inInts = array_unique($inInts);
        foreach ($inInts as $interest) {
            $ints = Interests::where('id', $interest)->first();
            if ($ints) {
                $this->return["interests"][] = [
                    "interestId" => isset($ints->id) ? $ints->id : 0,
                    "interestName" => isset($ints->name) ? CommonHelper::returnCurrentLangField($ints, 'name') : null,
                    "interestSlug" => isset($ints->interest_slug) ? $ints->interest_slug : null,
                ];
            }
        }
    }

    private function setItemOccasions($product)
    {
        $occasions = $product->occasions;
        foreach ($occasions as $occasion) {
            $occ = Occasion::where('id', $occasion->occasion_id)->first();
            if ($occ) {
                $this->return["occasions"][] = [
                    "occasionId" => isset($occ->id) ? $occ->id : 0,
                    "occasionName" => isset($occ->name) ? CommonHelper::returnCurrentLangField($occ, 'name') : null,
                    "occasionSlug" => isset($occ->occasion_slug) ? $occ->occasion_slug : null,
                ];
            }
        }
    }

    public function checkInArray($request, $array)
    {
        $inArray = true;
        foreach ($request as $req) {
            if (!in_array($req, $array)) {
                $inArray = false;
                break;
            }
        }
        return $inArray;
    }

    private function setItemSelectedAttrData($product)
    {
        $this->return['productselectedAttributes'] = [];
        $values = $product->valuesData;
        if ($values->count() > 0) {
            foreach ($values as $itemValue) {
                $value = $itemValue->valueMainData;
                $this->return['productselectedAttributes'][$value->product_attribute_id] = (string) $value->id;
            }
        }
    }
}
