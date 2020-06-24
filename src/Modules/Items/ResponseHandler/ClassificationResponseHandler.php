<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Classification;
use League\Fractal;

class ClassificationResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(Classification $data) {
        $this->data = $data;
        $this->setDefaultData();
        //$this->setCategoriesData();
        return $this->return;
    }

    private function setDefaultData() {
        $className = isset($this->data->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'name') : NULL;
        $this->return = [
            "classID" => isset($this->data->id) ? $this->data->id : 0,
            "classSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->data, 'class_slug', $className),
            "className" => $className,
        ];
    }

//    private function setCategoriesData() {
//        $this->return['childsData'] = [];
//        $items = $this->data->itemsMainData;
//        if ($items) {
//            $addedCats = [];
//            foreach ($items as $item) {
//                $category = $item->category;
//                if (isset($category->id) && !in_array($category->id, $addedCats)) {
//                    $this->return['childsData'][] = [
//                        'classID' => isset($category->id) ? $category->id : 0,
//                        "classSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($category, 'category_slug', \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($category, 'name')),
//                        'className' => isset($category->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($category, 'name') : NULL,
//                    ];
//                    $addedCats[] = $category->id;
//                }
//            }
//        }
//    }

}
