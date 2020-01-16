<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserModel;
use League\Fractal;

class ProfileInfoResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(UserModel $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $userCalendars = $this->data->calendars;
        $userWishList = $this->data->wishLish;
        $participants = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('user_id', $this->data->id)->count();
        $request = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest(\Illuminate\Http\Request::capture());
        $upcomingEvents = 0;
        if(isset($request["requestData"]['userId']) && count($request["requestData"]['userId'])){
            $upcomingEvents = \OlaHub\UserPortal\Models\CalendarModel::whereIn('user_id', $request["requestData"]['userId'])->where('calender_date', "<=", date("Y-m-d H:i:s", strtotime("+30 days")))->where('calender_date', ">", date("Y-m-d H:i:s"))->orderBy('calender_date', 'desc')->count();
        }
        $this->return = [
            "user" => isset($this->data->id) ? $this->data->id : 0,
            "userFullName" => isset($this->data->first_name) ? $this->data->first_name . ' ' . $this->data->last_name : NULL,
            "userCalendar" => $userCalendars->count() > 0 ? $userCalendars->count() : 0,
            "userWishList" => $userWishList->count() > 0 ? $userWishList->count() : 0,
            "userCelebrations" => $participants,
            "userUpcomingEvents" => $upcomingEvents,
        ];
    }
    

}
