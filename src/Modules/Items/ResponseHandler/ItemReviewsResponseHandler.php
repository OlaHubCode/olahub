<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\ItemReviews;
use League\Fractal;

class ItemReviewsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(ItemReviews $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $userData = $this->data->userMainData;
        $this->return = [
            "reviewID" => isset($this->data->id) ? $this->data->id : 0,
            "reviewRate" => isset($this->data->rating) ? $this->data->rating : 0,
            "reviewUser" => isset($userData->first_name) ?  "$userData->first_name $userData->last_name" : NULL,
            "reviewContent" => $this->data->review,
            "reviewUserImage" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
            "reviewForCurrent" => false,
            "reviewDate" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($this->data->created_at),
        ];
        if($this->data->user_id == app('session')->get('tempID')){
            $this->return['reviewForCurrent'] = true;
        }
    }
}
