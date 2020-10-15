<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserModel;
use League\Fractal;

class UserSearchResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;

    public function transform(UserModel $data)
    {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData()
    {
        $status = \OlaHub\UserPortal\Models\Friends::checkStatus(app('session')->get('tempID'), $this->data->id);
        $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList(app('session')->get('tempID'));
        $userFriends = (\OlaHub\UserPortal\Models\Friends::getFriendsList($this->data->id));
        $mutualFriends = count(array_intersect($userFriends, $friends));
        $this->return = [
            "itemId" => isset($this->data->id) ? $this->data->id : 0,
            "itemName" => isset($this->data->first_name) ? $this->data->first_name . ' ' . $this->data->last_name : NULL,
            "itemImage" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->profile_picture),
            "itemSlug" => \OlaHub\UserPortal\Models\UserModel::getUserSlug($this->data),
            "mutualFriends" => $mutualFriends,
            "status" => $status,
            "loading" => " ",
            "itemType" => 'user'
        ];
    }
}
