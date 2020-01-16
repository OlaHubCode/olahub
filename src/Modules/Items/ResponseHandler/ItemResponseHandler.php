<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\CatalogItem;
use League\Fractal;
use Illuminate\Http\Request;

class ItemResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;
    private $parentData;
    private $request;

    public function transform(CatalogItem $data) {
        $this->request = Request::capture();
        $this->data = $data;
        if ($data->parent_item_id > 0) {
            $this->parentData = $data->templateItem;
        } else {
            $this->parentData = $data;
        }
        $this->setDefaultData();
        $this->setRateData();
        $this->setPriceData();
        $this->setBrandData();
        $this->setMerchantData();
        $this->setAddData();
        //$this->setAttrData();
        $this->setItemSelectedAttrData();
        $this->setDefImageData();
        $this->setShippingData();
        $this->setItemCategories();
        $this->setItemClassifications();
        $this->setItemOccasions();
        $this->setItemInterests();
        return $this->return;
    }

    private function setDefaultData() {
        $itemName = isset($this->parentData->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->parentData, 'name') : NULL;
        $itemDescription = isset($this->parentData->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->parentData, 'description') : NULL;
        $customizeData = isset($this->data->customize_type)?unserialize($this->data->customize_type):0;
        $this->return = [
            "productID" => isset($this->data->id) ? $this->data->id : 0,
            "productShowLabel" => isset($this->data->show_discount_label) ? $this->data->show_discount_label : 1,
            "productSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->parentData, 'item_slug', $itemName),
            "productSKU" => isset($this->parentData->sku) ? $this->parentData->sku : NULL,
            "productName" => $itemName,
            "productDescription" => $itemDescription,
            "productIsCustomized" => $this->data->is_customized,
            "productIsMustCustom" => $this->data->is_must_custom,
            "productCustomizeType" => !empty($customizeData)?$customizeData['customization_details']:0,
            "productCustomizeLength" => !empty($customizeData)?$customizeData['character_length']:0,
            "productInStock" => CatalogItem::checkStock($this->data),
            "productIsNew" => CatalogItem::checkIsNew($this->data),
        ];
    }

    private function setRateData() {
        $this->return['productRate'] = $this->parentData->item_rate;
        
        $this->return['currentUserRate'] = 0;
        if (app('session')->get('tempID')) {
            $checkRate = $this->parentData->reviewsData;
            $productRate = 0;
            $rater = 0;
            if ($checkRate->count()) {
                foreach ($checkRate as $rate){
                   $rater += 1;
                   $productRate += $rate->rating;
                   if($rate->user_id == app('session')->get('tempID') && $rate->rating > 0){
                       $this->return['currentUserRate'] = $rate->rating;
                   }
                }
                $this->return['productRate'] = (int)($productRate / $rater);
                $this->parentData->item_rate = $this->return['productRate'];
                $this->parentData->save();
            }
        }
    }

    private function setPriceData() {
        $return = CatalogItem::checkPrice($this->data);
        $this->return['productPrice'] = $return['productPrice'];
        $this->return['productDiscountedPrice'] = $return['productDiscountedPrice'];
        $this->return['productHasDiscount'] = $return['productHasDiscount'];
    }

    private function setMerchantData() {
        $user = app('session')->get('tempID') ? \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first() : false;
        $brand = $this->parentData->brand;
        $this->return["productOwner"] = isset($brand->id) ? $brand->id : NULL;
        $this->return["productOwnerName"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($brand, 'name');
        $this->return["productOwnerSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($brand, 'store_slug', $this->return["productOwnerName"]);
        $this->return["followed"] = $user && isset($user->followed_brands) && is_array($user->followed_brands) && in_array($brand->id, $user->followed_brands) ? true : false;
    }

    private function setBrandData() {
        $brandData = $this->parentData->brand;
        $this->return["productBrand"] = 0;
        $this->return["productBrandName"] = null;
        $this->return["productBrandSlug"] = null;
        $this->return['productBrandLogo'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        $this->return["productOwnerFollowed"] = 0;
        $this->return["productOwnerFollowers"] = 0;
        if ($brandData) {
            $this->return["productBrand"] = isset($brandData->id) ? $brandData->id : NULL;
            $this->return["productBrandName"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($brandData, 'name');
            $this->return["productBrandSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($brandData, 'store_slug', $this->return["productBrandName"]);
            $this->return['productBrandLogo'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brandData->image_ref);
            $this->setFollowStatus($brandData);
        }
    }
    
    private function setFollowStatus($brand) {
        $this->return["productOwnerFollowed"] = 0;
        $this->return["productOwnerFollowers"] = 0;
        if (app('session')->get('tempID')) {
            $user = \OlaHub\UserPortal\Models\UserMongo::where("user_id", app('session')->get('tempID'))->first();
            if ($user) {
                $brands = $user->followed_brands && is_array($user->followed_brands) ? $user->followed_brands : [];
                if (in_array($brand->id, $brands)) {
                    $this->return["productOwnerFollowed"] = 1;
                }
            }
        }
        $followers = \OlaHub\UserPortal\Models\UserMongo::whereIn("followed_brands", [(string) $brand->id, (int) $brand->id])->count();
        $this->return["productOwnerFollowers"] = $followers;
    }

    private function setAddData() {
        $this->return['productWishlisted'] = '0';
        $this->return['productLiked'] = '0';
        $this->return['productShared'] = '0';
        $this->return['productInCart'] = 0;

        //wishlist
        if (\OlaHub\UserPortal\Models\WishList::where('item_id', $this->data->id)->count() > 0) {
            $this->return['productWishlisted'] = '1';
        }

        //like
        $post = \OlaHub\UserPortal\Models\Post::where('item_slug', $this->data->item_slug)->where('type', 'item_post')->first();
        if ($post && isset($post->likes) && in_array(app('session')->get('tempID'), $post->likes)) {
            $this->return['productLiked'] = '1';
        }
        
        //share
        if ($post && isset($post->shares) && in_array(app('session')->get('tempID'), $post->shares)) {
            $this->return['productShared'] = '1';
        }

        //Cart
        
        if(app('session')->get('tempID')){
            $itemID = $this->data->id;
            if (\OlaHub\UserPortal\Models\Cart::whereHas('cartDetails', function ($q) use($itemID) {
                        $q->where('item_id', $itemID);
                        $q->where("item_type", "store");
                    })->count() > 0) {
                $this->return['productInCart'] = 1;
            }
        } else {
            $this->checkNotLogeedCart();
        }
        
    }
    
    private function checkNotLogeedCart() {
        $cartCookie = $this->request->headers->get("cartCookie") ? json_decode($this->request->headers->get("cartCookie")) : [];
        if ($cartCookie && is_array($cartCookie) && count($cartCookie) > 0) {
            $id = $this->data->id;
            foreach ($cartCookie as $item) {
                if ($id == $item->productId) {
                    $this->return['productInCart'] = 1;
                    return;
                }
            }
        }
    }

    /*private function setAttrData() {
        $values = \OlaHub\UserPortal\Models\ItemAttrValue::where('parent_item_id', $this->parentData->id)->get();
        $addedParnts = [];
        $this->return['productAttributes'] = [];
        foreach ($values as $itemValue) {
            $value = $itemValue->valueMainData;
            if (in_array($value->product_attribute_id, $addedParnts)) {
                $this->return['productAttributes'][$value->product_attribute_id]['childsData'][] = [
                    "value" => isset($value->id) ? $value->id : 0,
                    "text" => isset($value->attribute_value) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($value, 'attribute_value') : NULL,
                ];
            } else {
                $parent = $value->attributeMainData;
                $this->return['productAttributes'][$value->product_attribute_id] = [
                    "valueID" => isset($parent->id) ? $parent->id : 0,
                    "valueName" => isset($parent->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($parent, 'name') : NULL,
                ];
                $this->return['productAttributes'][$value->product_attribute_id]['childsData'][] = [
                    "value" => isset($value->id) ? $value->id : 0,
                    "text" => isset($value->attribute_value) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($value, 'attribute_value') : NULL,
                ];
                $addedParnts[] = $value->product_attribute_id;
            }
        }
    }*/

    private function setItemSelectedAttrData() {
        $this->return['productselectedAttributes'] = [];
        $values = $this->data->valuesData;
        if ($values->count() > 0) {
            foreach ($values as $itemValue) {
                $value = $itemValue->valueMainData;
                $this->return['productselectedAttributes'][$value->product_attribute_id] = (string)$value->id;
            }
        }
    }

    private function setDefImageData() {
        $images = $this->data->images;
        if ($images->count() > 0) {
            foreach ($images as $image) {
                $this->return['productImages'][] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image->content_ref);
            }
        } else {
            $this->return['productImages'][] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setShippingData() {
        //start from
        $from = 3;
        if ($this->data->estimated_shipping_time > 0) {
            $from = $this->data->estimated_shipping_time;
        }
        $estimatedShippingTime = strtotime("+$from Days");
        $date = date('M d, Y', $estimatedShippingTime);
        //end from
        
        //start to
        $dateTo = false;
        if ($this->data->max_shipping_days > 0 && $this->data->max_shipping_days > $this->data->estimated_shipping_time) {
            $to = $this->data->max_shipping_days;
            $estimatedShippingTimeTo = strtotime("+$to Days");
            $dateTo = date('M d, Y', $estimatedShippingTimeTo);
        }
        //end to
        
        $this->return['shippingDateFrom'] = $date;
        $this->return['shippingDateTo'] = $dateTo;
        $exchange = $this->data->exchangePolicy;
        $this->return['exchangePolicy'] = isset($exchange->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($exchange, 'name') : null;
    }
    
    private function setItemCategories(){
        $category = \OlaHub\UserPortal\Models\ItemCategory::where('id', $this->data->category_id)->first();
        if(!$category){
            return;
        }
        if($category->parent_id > 0){
            $this->return["subCategories"][] = [
                "subCategoryId" => isset($category->id) ? $category->id : 0,
                "subCategoryName" => isset($category->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($category, 'name'): null,
                "subCategorySlug" => isset($category->category_slug) ? $category->category_slug : null,
            ];
            $parentCategory = \OlaHub\UserPortal\Models\ItemCategory::where('id', $category->parent_id)->first();
            $this->return["categories"][] = [
                "categoryId" => isset($parentCategory->id) ? $parentCategory->id : 0,
                "categoryName" => isset($parentCategory->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($parentCategory, 'name'): null,
                "categorySlug" => isset($parentCategory->category_slug) ? $parentCategory->category_slug : null,
            ];
        } else {
            $this->return["categories"][] = [
                "categoryId" => isset($category->id) ? $category->id : 0,
                "categoryName" => isset($category->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($category, 'name'): null,
                "categorySlug" => isset($category->category_slug) ? $category->parent_id : null,
            ];
        }
    }
    
    private function setItemClassifications(){
        $classification = \OlaHub\UserPortal\Models\Classification::where('id', $this->data->clasification_id)->first();
        if($classification){
            $this->return["classifications"][] = [
                "classificationId" => isset($classification->id) ? $classification->id : 0,
                "classificationName" => isset($classification->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($classification, 'name') : null,
                "classificationSlug" => isset($classification->class_slug) ? $classification->class_slug : null,
            ];
        }
    }
    
    private function setItemOccasions(){
        $occasions = $this->data->occasions;
        foreach ($occasions as $occasion){
            $occ = \OlaHub\UserPortal\Models\Occasion::where('id', $occasion->occasion_id)->first();
            if($occ){
                $this->return["occasions"][] = [
                    "occasionId" => isset($occ->id) ? $occ->id : 0,
                    "occasionName" => isset($occ->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occ, 'name') : null,
                    "occasionSlug" => isset($occ->occasion_slug) ? $occ->occasion_slug : null,
                ];
            }
        }
    }
    
    private function setItemInterests(){
        $interests = \OlaHub\UserPortal\Models\Interests::whereIn('items', [$this->data->id])->get();
        foreach ($interests as $interest){
            $this->return["interests"][] = [
                "interestId" => isset($interest->interest_id) ? $interest->interest_id : 0,
                "interestName" => isset($interest->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($interest, 'name') : null,
                "interestSlug" => isset($interest->interest_slug) ? $interest->interest_slug : null,
            ];
        }
    }

}
