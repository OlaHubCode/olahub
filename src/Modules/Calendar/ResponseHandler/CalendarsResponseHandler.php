<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\CalendarModel;
use League\Fractal;

class CalendarsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(CalendarModel $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setDates();
        return $this->return;
    }

    private function setDefaultData() {
        $occassion = $this->data->occasion;
        $this->return = [
            "calendar" => isset($this->data->id) ? $this->data->id : 0,
            "calendarDate" => isset($this->data->calender_date) ? $this->data->calender_date : NULL,
            "calendarOccassion" => isset($this->data->occasion_id) ? $this->data->occasion_id : NULL,
            "calendarOccassionName" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occassion, 'name'),
            "calendarOccassionLogo" => isset($occassion->logo_ref) && $occassion->logo_ref ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($occassion->logo_ref) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
            "calendarTitle" => isset($this->data->title) ? $this->data->title : NULL,
            "calendarAnnual" => isset($this->data->is_annual) ? (bool)$this->data->is_annual : NULL,
        ];
    }
    
    private function setDates() {
        $this->return["created"] = isset($this->data->created_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($this->data->created_at) : NULL;
        $this->return["creator"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::defineRowCreator($this->data);
        $this->return["updated"] = isset($this->data->updated_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($this->data->updated_at) : NULL;
        $this->return["updater"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::defineRowCreator($this->data);
    }

}
