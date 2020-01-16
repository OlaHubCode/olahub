<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\CalendarModel;
use League\Fractal;

class UpcomingEventsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(CalendarModel $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setDefProfileImageData();
        return $this->return;
    }

    private function setDefaultData() {
        $occassion = $this->data->occasion;
        $this->return = [
            "calendar" => isset($this->data->id) ? $this->data->id : 0,
            "calendarDate" => isset($this->data->calender_date) ? $this->data->calender_date : NULL,
            "calendarOccassion" => isset($this->data->occasion_id) ? $this->data->occasion_id : NULL,
            "calendarOccassionName" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occassion, 'name'),
            "calendarTitle" => isset($this->data->title) ? $this->data->title : NULL,
        ];
    }
    
    private function setDefProfileImageData() {
        $user = \OlaHub\UserPortal\Models\UserModel::where('id',$this->data->user_id)->first();
        if (isset($user->profile_picture)) {
            $this->return['userPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($user->profile_picture);
        } else {
            $this->return['userPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
    

}
