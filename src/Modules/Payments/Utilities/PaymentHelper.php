<?php

namespace OlaHub\UserPortal\Helpers;

use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;

class PaymentHelper extends OlaHubCommonHelper {

    function createUserBillNumber() {
        $lastBill = \OlaHub\UserPortal\Models\UserBill::where('country_id', app('session')->get('def_country')->id)->latest()->first();
        $billNumber = 0;
        if ($lastBill) {
            $billNumberTemp = explode('-', $lastBill->billing_number);
            $billNumber = (int) end($billNumberTemp);
        }
        $billNumber += 1;
        return BILL_PREF . strtoupper(app('session')->get('def_country')->two_letter_iso_code) . "-" . parent::createNumberPrefix(app('session')->get('tempID')) . "-" . parent::createNumberPrefix($billNumber, 2);
    }

    function getBillDetails($oneItem, $itemPrice) {
        $details = [];
        if ($oneItem) {
            $details['merchant'] = $this->getBillMerchantDetails($oneItem->merchant);
            $details['brand'] = $this->getBillBrandDetails($oneItem->brand);
            $details['category'] = $this->getBillCategoryDetails($oneItem->category);
            $details['classification'] = $this->getBillClassifcationDetails($oneItem->classification);
            $details['occasions'] = $this->getBillOccasionsDetails($oneItem->occasions);
            $details['attributes'] = $this->getBillAttributesDetails($oneItem->valuesData, $oneItem->parentValuesData);
            $details['pickup'] = $this->getBillPickupAddressDetails($oneItem);
            $details['price'] = $itemPrice;
            $details['is_voucher'] = $oneItem->is_voucher;
            $details['id'] = $oneItem->id;
        }
        return $details;
    }

    private function getBillMerchantDetails($merchant) {
        $return = [];
        if ($merchant) {
            $return['name'] = $merchant->company_legal_name;
            $return['slug'] = $merchant->merchant_slug;
            $return['logo'] = $merchant->company_logo_ref;
            $return['id'] = $merchant->id;
        }
        return $return;
    }

    private function getBillBrandDetails($brand) {
        $return = [];
        if ($brand) {
            $return['name'] = isset($brand->name) ? $brand->name : null;
            $return['slug'] = isset($brand->brand_slug) ? $brand->brand_slug : null;
            $return['logo'] = isset($brand->image_ref) ? $brand->image_ref : null;
        }
        return $return;
    }

    private function getBillPickupAddressDetails($oneItem) {
        $return = 0;
        $pickup = \OlaHub\UserPortal\Models\ItemPickuAddr::where("item_id", $oneItem->id)->first();
        if ($pickup) {
            $return = isset($pickup->pickup_address_id) ? $pickup->pickup_address_id : 0;
        }
        return $return;
    }

    private function getBillCategoryDetails($category) {
        $return = [];
        if ($category) {
            $return['name'] = $category->name;
            $return['slug'] = $category->category_slug;
            $catCountry = \OlaHub\UserPortal\Models\ManyToMany\ItemCountriesCategory::where('country_id', app('session')->get('def_country')->id)
                            ->where('category_id', $category->id)->first();
            $return['catCountry'] = [
                "id" => isset($catCountry->id) ? $catCountry->id : 0,
                "country_id" => isset($catCountry->country_id) ? $catCountry->country_id : 0,
                "category_id" => isset($catCountry->category_id) ? $catCountry->category_id : 0,
                "is_published" => isset($catCountry->is_published) ? $catCountry->is_published : 1,
                "commission_percentage" => isset($catCountry->commission_percentage) ? $catCountry->commission_percentage : 0,
                "cross_commission_perc" => isset($catCountry->cross_commission_perc) ? $catCountry->cross_commission_perc : 0,
            ];
        }
        return $return;
    }

    private function getBillClassifcationDetails($classification) {
        $return = [];
        if ($classification) {
            $return['name'] = isset($classification->name) ? $classification->name : null;
            $return['slug'] = isset($classification->class_slug) ? $classification->class_slug : null;
        }
        return $return;
    }

    private function getBillOccasionsDetails($occasions) {
        $return = [];
        foreach ($occasions as $oneOccasion) {
            $occasionDetails = $oneOccasion->occasionMainData;
            $return[] = array(
                'name' => isset($occasionDetails->name) ? $occasionDetails->name : null,
                'slug' => isset($occasionDetails->occasion_slug) ? $occasionDetails->occasion_slug : null,
            );
        }
        return $return;
    }

    private function getBillAttributesDetails($valuesData) {
        $return = [];
        foreach ($valuesData as $value) {
            $valueMain = $value->valueMainData;
            $attribute = $valueMain->attributeMainData;
            $return[] = array(
                'name' => $attribute->name,
                'value' => $valueMain->attribute_value,
            );
        }
        return $return;
    }

    function getBillDesignerDetails($oneItem, $mainItem, $itemPrice) {
        $details = [];
        if ($oneItem) {
            $details['merchant'] = $this->getBillOwnerDetails($mainItem);
            $details['category'] = $this->getBillDesignerCategoryDetails($mainItem);
            $details['classification'] = $this->getBillDesignerClassifcationDetails($mainItem);
            $details['occasions'] = $this->getBillDesignerOccasionsDetails($mainItem);
            $details['attributes'] = $this->getBillDesignerAttributesDetails($oneItem);
            $details['price'] = $itemPrice;
            $details['is_voucher'] = isset($oneItem->is_voucher) ? $oneItem->is_voucher : 0;
            $details['id'] = $oneItem->item_id;
        }
        return $details;
    }

    private function getBillOwnerDetails($mainItem) {
        $return = [];
        $owner = \OlaHub\UserPortal\Models\Designer::where("id", $mainItem->designer_id)->first();
        if ($owner) {
            $return['name'] = $owner->brand_name;
            $return['slug'] = $owner->designer_slug;
            $return['logo'] = $owner->logo_ref;
            $return['id'] = $owner->id;
        }
        return $return;
    }

    private function getBillDesignerCategoryDetails($mainItem) {
        $return = [];
        $category = \OlaHub\UserPortal\Models\ItemCategory::where("id", $mainItem->item_parent_category_id)->first();
        $subCategory = \OlaHub\UserPortal\Models\ItemCategory::where("id", $mainItem->item_sub_category_id)->first();
        if ($category && $subCategory) {
            $return['parent_name'] = $category->name;
            $return['parent_slug'] = $category->category_slug;
            $return['child_name'] = $category->name;
            $return['child_slug'] = $category->category_slug;
        }
        return $return;
    }

    private function getBillDesignerClassifcationDetails($mainItem) {
        $return = [];
        $classification = \OlaHub\UserPortal\Models\Classification::where("id", $mainItem->item_classification_id)->first();
        if ($classification) {
            $return['name'] = isset($classification->name) ? $classification->name : null;
            $return['slug'] = isset($classification->class_slug) ? $classification->class_slug : null;
        }
        return $return;
    }

    private function getBillDesignerOccasionsDetails($mainItem) {
        $return = [];
        if (is_array($mainItem->item_occasion_ids) && count($mainItem->item_occasion_ids) > 0) {
            $occasions = \OlaHub\UserPortal\Models\Occasion::withoutGlobalScope("country")->whereIn("id", $mainItem->item_occasion_ids)->get();
            foreach ($occasions as $occasionDetails) {
                $return[] = array(
                    'name' => isset($occasionDetails->name) ? $occasionDetails->name : null,
                    'slug' => isset($occasionDetails->occasion_slug) ? $occasionDetails->occasion_slug : null,
                );
            }
        }
        return $return;
    }

    private function getBillDesignerAttributesDetails($oneItem) {
        $return = [];
        if (isset($oneItem->item_attr) && is_array($oneItem->item_attr) && count($oneItem->item_attr)) {
            $valuesData = \OlaHub\UserPortal\Models\AttrValue::whereIn("id", $oneItem->item_attr)->get();
            foreach ($valuesData as $valueMain) {
                $attribute = $valueMain->attributeMainData;
                $return[] = array(
                    'name' => $attribute->name,
                    'value' => $valueMain->attribute_value,
                );
            }
        }
        return $return;
    }

    static function groupBillMerchants($billDetails) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Payment for group bill merchants", "action_startData" => $billDetails]);
        $return = [
            'voucher' => 0,
        ];
        foreach ($billDetails as $item) {
            $details = unserialize($item->item_details);
            switch ($item->item_type) {
                case "store":

                    if (isset($details['is_voucher']) && $details['is_voucher'] == 1) {
                        $return['voucher'] += $item->item_price * $item->quantity;
                    }

                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Get pick up for address details", "action_startData" => $details]);
                    $itemStorePickup = PaymentHelper::getPickubAddressDetails($details);
                    if ($itemStorePickup) {
                        $pickup = $itemStorePickup->pickupData;
                        if (!array_key_exists($itemStorePickup->store_id, $return)) {
                            $store = $itemStorePickup->storeData;
                            $return[$itemStorePickup->store_id] = [
                                'storeName' => $store->name,
                                'storeManagerName' => $store->contact_full_name,
                                'storeEmail' => $store->contact_email,
                                'storePhone' => $store->contact_phone_no,
                            ];
                        }
                        $customItem = [];
                        if ($item->customize_data != null) {
                            $customItem = unserialize($item->customize_data);
                        }

                        $return[$itemStorePickup->store_id]['items'][] = [
                            'itemName' => OlaHubCommonHelper::returnCurrentLangField($item, 'item_name'),
                            'itemImage' => OlaHubCommonHelper::setContentUrl($item->item_image),
                            'itemPrice' => $item->item_price,
                            'itemQuantity' => $item->quantity,
                            'itemTotal' => $item->item_price * $item->quantity,
                            'itemAttributes' => $details['attributes'],
                            'fromPickupAddress' => isset($pickup->street_address) ? $pickup->street_address : '',
                            'fromPickupCity' => isset($pickup->city) ? $pickup->city : '',
                            'fromPickupRegion' => isset($pickup->region) ? $pickup->region : '',
                            'fromPickupZipCode' => isset($pickup->zip_code) ? $pickup->zip_code : '',
                            'fromPickupID' => isset($pickup->id) ? $pickup->id : 0,
                            'itemCustomImage' => isset($customItem ["image"]) ? $customItem ["image"] : '',
                            'itemCustomText' => isset($customItem["text"]) ? $customItem["text"] : '',
                        ];

                        $itemStorePickup->quantity -= $item->quantity;
                        $itemStorePickup->save();
                        $details['pickup'] = [
                            "id" => $itemStorePickup->id,
                            "item_id" => $itemStorePickup->item_id,
                            "store_id" => $itemStorePickup->store_id,
                            "pickup_address_id" => $itemStorePickup->pickup_address_id,
                            "quantity" => $itemStorePickup->quantity,
                        ];
                        $item->from_pickup_id = $itemStorePickup->pickup_address_id;
                        $item->item_details = serialize($details);
                        $item->save();
                    }
                    break;
                case "designer":
                    $designer = \OlaHub\UserPortal\Models\Designer::where('id', $item->store_id)->first();
                    if ($designer) {
                        if (!array_key_exists($item->store_id, $return)) {
                            $return[$item->store_id] = [
                                'storeName' => $designer->brand_name,
                                'storeManagerName' => $designer->contact_full_name,
                                'storeEmail' => $designer->contact_email,
                                'storePhone' => $designer->contact_phone_no,
                            ];
                        }

                        $customItem = [];
                        if ($item->customize_data != null) {
                            $customItem = unserialize($item->customize_data);
                        }
                        $return[$item->store_id]['items'][] = [
                            'itemName' => OlaHubCommonHelper::returnCurrentLangField($item, 'item_name'),
                            'itemImage' => OlaHubCommonHelper::setContentUrl($item->item_image),
                            'itemPrice' => $item->item_price,
                            'itemQuantity' => $item->quantity,
                            'itemTotal' => $item->item_price * $item->quantity,
                            'itemAttributes' => $details['attributes'],
                            'fromPickupAddress' => isset($designer->full_address) ? $designer->full_address : '',
                            'fromPickupCity' => isset($designer->city) ? $designer->city : '',
                            'fromPickupRegion' => '',
                            'fromPickupZipCode' => '',
                            'fromPickupID' => 0,
                            'itemCustomImage' => isset($customItem ["image"]) ? $customItem ["image"] : '',
                            'itemCustomText' => isset($customItem["text"]) ? $customItem["text"] : '',
                        ];
                    }

                    break;
            }
        }
        return $return;
    }

    static function groupBillMerchantForCancelRefund($item) {
        $return = "";
        $details = unserialize($item->item_details);
        $itemStorePickup = \OlaHub\UserPortal\Models\ItemPickuAddr::where("id", $item->from_pickup_id)->first();
        if ($itemStorePickup) {
            $pickup = $itemStorePickup->pickupData;
            $store = $itemStorePickup->storeData;
            $return = [
                'storeName' => $store->name,
                'storeManagerName' => $store->contact_full_name,
                'storeEmail' => $store->contact_email,
                'storePhone' => $store->contact_phone_no,
            ];
            $customItem = [];
            if ($item->customize_data != null) {
                $customItem = unserialize($item->customize_data);
            }

            $return['item'] = [
                'itemName' => OlaHubCommonHelper::returnCurrentLangField($item, 'item_name'),
                'itemImage' => OlaHubCommonHelper::setContentUrl($item->item_image),
                'itemPrice' => $item->item_price,
                'itemQuantity' => $item->quantity,
                'itemTotal' => $item->item_price * $item->quantity,
                'itemAttributes' => $details['attributes'],
                'fromPickupAddress' => isset($pickup->street_address) ? $pickup->street_address : '',
                'fromPickupCity' => isset($pickup->city) ? $pickup->city : '',
                'fromPickupRegion' => isset($pickup->region) ? $pickup->region : '',
                'fromPickupZipCode' => isset($pickup->zip_code) ? $pickup->zip_code : '',
                'fromPickupID' => isset($pickup->id) ? $pickup->id : 0,
                'itemCustomImage' => isset($customItem ["image"]) ? $customItem ["image"] : '',
                'itemCustomText' => isset($customItem["text"]) ? $customItem["text"] : '',
            ];

            $itemStorePickup->quantity += $item->quantity;
            $itemStorePickup->save();
        }
        return $return;
    }

    static function getPickubAddressDetails($details) {
        return \OlaHub\UserPortal\Models\ItemPickuAddr::where('item_id', $details['id'])
                        ->whereHas('storeData', function($q) use($details) {
                            $q->where('merchant_id', $details['merchant']['id']);
                        })
                        ->orderBy('quantity', 'DESC')
                        ->first();
    }

    function sendSellersNewOrdersNotifications($fcmTokens) {
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60 * 20);

        $notificationBuilder = new PayloadNotificationBuilder('New Order');
        $notificationBuilder->setBody('You have got new orders')
                ->setSound('default');

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData(['a_data' => 'my_data']);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

        // You must change it to get your tokens
        $tokens = $fcmTokens;

        $downstreamResponse = FCM::sendTo($tokens, $option, $notification, $data);

        $downstreamResponse->numberSuccess();
        $downstreamResponse->numberFailure();
        $downstreamResponse->numberModification();

        // return Array - you must remove all this tokens in your database
        $downstreamResponse->tokensToDelete();

        // return Array (key : oldToken, value : new token - you must change the token in your database)
        $downstreamResponse->tokensToModify();

        // return Array - you should try to resend the message to the tokens in the array
        $downstreamResponse->tokensToRetry();

        // return Array (key:token, value:error) - in production you should remove from your database the tokens present in this array
        $downstreamResponse->tokensWithError();
    }

}
