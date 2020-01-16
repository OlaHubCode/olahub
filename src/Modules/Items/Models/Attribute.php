<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model {

    protected $table = 'catalog_item_attributes';

    public function valuesData() {
        return $this->hasMany('OlaHub\UserPortal\Models\AttrValue', 'product_attribute_id');
    }

    static function setReturnResponse($attributes, $itemsIDs = false, $first = false) {
        $return['data'] = [];
        foreach ($attributes as $attribute) {
            $attrData = [
                "valueID" => isset($attribute->id) ? $attribute->id : 0,
                "valueName" => isset($attribute->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($attribute, 'name') : NULL,
                "valueColorStyle" => isset($attribute->is_color_style) ? $attribute->is_color_style : 0,
                "valueSizeStyle" => isset($attribute->is_size_style) ? $attribute->is_size_style : 0,
            ];

            $attrData['childsData'] = [];
            if ($itemsIDs) {
                $childs = $attribute->valuesData()->whereHas('valueItemsData', function($q) use($itemsIDs, $first) {
                            if ($first) {
                                $q->whereIn('parent_item_id', $itemsIDs);
                            } else {
                                $q->whereIn('item_id', $itemsIDs);
                            }
                        })->groupBy('id')->get();
            } else {
                $childs = $attribute->childsData()->has('itemsMainData')->groupBy('id')->get();
            }
            foreach ($childs as $child) {
                $attrData['childsData'][] = [
                    "valueID" => isset($child->id) ? $child->id : 0,
                    "valueName" => isset($child->attribute_value) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($child, 'attribute_value') : NULL,
                    "valueHexColor" => isset($child->color_hex_code) ? $child->color_hex_code : NULL,
                ];
            }
            $return['data'][] = $attrData;
        }
        return (array) $return;
    }

    static function setOneProductReturnResponse($attributes, $itemsIDs = false, $first = false) {
        $return['data'] = [];
        foreach ($attributes as $attribute) {
            $attrData = [
                "valueID" => isset($attribute->id) ? $attribute->id : 0,
                "valueName" => isset($attribute->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($attribute, 'name') : NULL,
                "valueColorStyle" => isset($attribute->is_color_style) ? $attribute->is_color_style : 0,
                "valueSizeStyle" => isset($attribute->is_size_style) ? $attribute->is_size_style : 0,
            ];

            $attrData['childsData'] = [];
            if ($itemsIDs) {
                $childs = $attribute->valuesData()->whereHas('valueItemsData', function($q) use($itemsIDs, $first) {
                            if ($first) {
                                $q->whereIn('parent_item_id', $itemsIDs);
                            } else {
                                $q->whereIn('item_id', $itemsIDs);
                            }
                        })->groupBy('id')->get();
            } else {
                $childs = $attribute->childsData()->has('itemsMainData')->groupBy('id')->get();
            }
            foreach ($childs as $child) {
                $attrData['childsData'][] = [
                    "value" => isset($child->id) ? (string) $child->id : 0,
                    "text" => isset($child->attribute_value) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($child, 'attribute_value') : NULL,
                    "valueHexColor" => isset($child->color_hex_code) ? $child->color_hex_code : NULL,
                ];
            }
            $return['data'][] = $attrData;
        }
        return (array) $return;
    }

}
