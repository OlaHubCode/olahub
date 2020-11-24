<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\UserModel;
use OlaHub\UserPortal\Models\UserSessionModel;
use OlaHub\UserPortal\Models\CalendarModel;

class CalendarController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    protected $userAgent;
    protected $authorization;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
        $this->authorization = $request->header('Authorization');
    }

    /**
     * Get all stores by filters and pagination
     *
     * @param  Request  $request constant of Illuminate\Http\Request
     * @return Response
     */

    public function getAllOccassionByCountry($target = NULL)
    {
        $countryId = !empty($this->requestData['id']) ? $this->requestData['id'] : app('session')->get('def_country')->id;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Calendar", 'function_name' => "Get all occassion by country"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start fetching occassion by countries"]);
        $occassionsCountry = \OlaHub\UserPortal\Models\ManyToMany\occasionCountries::where('country_id', (int) $countryId)
            ->where(function ($q) use ($target) {
                if ($target == 'registry') {
                    $q->where('for_registry', 1);
                }
            })->get();
        if ($occassionsCountry->count() < 1) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }

        $return['Occassions'] = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($occassionsCountry, '\OlaHub\UserPortal\ResponseHandlers\OccassionsForPrequestFormsResponseHandler');
        $return['status'] = true;
        $return['code'] = 200;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End fetch occassion by countries"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

    public function createNewCalendar()
    {

        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(CalendarModel::$columnsMaping, (array) $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }
        $calendar = new CalendarModel;

        foreach ($this->requestData as $input => $value) {

            if ($input == "calendarAnnual") {
                $calendar->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(CalendarModel::$columnsMaping, $input)} = boolval($value);
            } else {
                $calendar->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(CalendarModel::$columnsMaping, $input)} = $value;
            }
        }
        $calendar->user_id = $userData->id;
        $saved = $calendar->save();
        if ($saved) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($calendar, '\OlaHub\UserPortal\ResponseHandlers\CalendarsResponseHandler');
            $return['status'] = true;

            $return['code'] = 200;
            $log->saveLog($userData->id, $this->requestData, 'Add Calender');

            return response($return, 200);
        }
        $log->saveLog($userData->id, $this->requestData, 'Add Calender');

        return response(['status' => false, 'msg' => 'InternalServerError', 'code' => 500], 200);
    }


    public function ListAllCalendars()
    {

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Calendar", 'function_name' => "List all calendars"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start listing all calenders"]);

        $calendars = CalendarModel::where('user_id', app('session')->get('tempID'))->orderBy('calender_date', 'ASC')->get();
        if ($calendars) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($calendars, '\OlaHub\UserPortal\ResponseHandlers\CalendarsResponseHandler');
            $return['status'] = true;

            $return['code'] = 200;
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response($return, 200);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End listing all calenders"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function deleteUserCalendar($id)
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
        $calendar = CalendarModel::where('user_id', app('session')->get('tempID'))->where('id', $id)->first();
        $return = $calendar->delete();
        $log->saveLog($userData->id, $this->requestData, 'Delete Calender');
        if ($return) {
            return response(['status' => true, 'msg' => 'calendarDeletedSuccussfully', 'code' => 200], 200);
        }

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function updateUserCalendar($id)
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(CalendarModel::$columnsMaping, (array) $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            $log->saveLog($userData->id, $this->requestData, 'Update Calender');
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }
        $calendar = CalendarModel::where('user_id', app('session')->get('tempID'))->where('id', $id)->first();
        foreach ($this->requestData as $input => $value) {
            if (isset(CalendarModel::$columnsMaping[$input])) {
                if ($input == "calendarAnnual") {
                    $calendar->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(CalendarModel::$columnsMaping, $input)} = (int)$value;
                } else {
                    $calendar->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(CalendarModel::$columnsMaping, $input)} = $value;
                }
            }
        }
        $updated = $calendar->save();
        $log->saveLog($userData->id, $this->requestData, 'Update Calender');
        if ($updated) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "User calender updated"]);
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($calendar, '\OlaHub\UserPortal\ResponseHandlers\CalendarsResponseHandler');
            $return['status'] = true;
            $return['code'] = 200;
            return response($return, 200);
        }

        return response(['status' => false, 'msg' => 'InternalServerError', 'code' => 500], 200);
    }


    public function getOneCalendar()
    {

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Calendar", 'function_name' => "Get one calendar"]);

        if (isset($this->requestData['calendarId']) && $this->requestData['calendarId'] > 0) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start get one calender"]);
            $calendar = CalendarModel::where('id', $this->requestData['calendarId'])->first();
            if ($calendar) {
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($calendar, '\OlaHub\UserPortal\ResponseHandlers\CalendarForCelebrationResponseHandler');
                $return['status'] = true;
                $return['code'] = 200;
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End get one calender"]);

                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                return response($return, 200);
            }
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }
}
