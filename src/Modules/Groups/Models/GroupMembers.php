<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMembers extends Model
{

    protected $table = 'groups_members';

    static function getGroups($id)
    {
        $groups = GroupMembers::select("group_id")->where('user_id', $id)->get();
        return \OlaHub\UserPortal\Models\groups::whereIn('id', $groups);
    }

    static function getGroupsArr($id)
    {
        $groups = GroupMembers::select("group_id")->where('user_id', $id)->get();
        $gs = [];
        foreach ($groups as $group) {
            array_push($gs, $group->group_id);
        }
        return $gs;
    }

    static function getMembersArr($id)
    {
        $members = GroupMembers::select("user_id")->where('group_id', $id)->get();
        $mems = [];
        foreach ($members as $member) {
            array_push($mems, $member->user_id);
        }
        return $mems;
    }

    static function getMembers($id)
    {
        $members = GroupMembers::where('group_id', $id)->get();
        $filtered = new \stdClass();
        $filtered->members = [];
        $filtered->responses = [];
        $filtered->requests = [];
        foreach ($members as $member) {
            if ($member->status == 1)
                array_push($filtered->members, $member->user_id);
            else if ($member->status == 2)
                array_push($filtered->responses, $member->user_id);
            elseif ($member->status == 3)
                array_push($filtered->requests, $member->user_id);
        }
        return $filtered;
    }
    static function getMembersOfCommonGroups($groups)
    {
        $members = GroupMembers::whereIn('group_id', $groups)->where('status', 1)->groupBy('user_id')->get();
        $mS = [];
        foreach ($members as $member) {
            $mS[] = $member->user_id;
        }
        return $mS;
    }
    static function getFriendsGroups($friends,$suggestedBefore)
    {
        $groups = GroupMembers::whereIn('user_id', $friends)->where('status', 1)->groupBy('group_id')->whereNotIn('group_id',$suggestedBefore)->get();
        $gS = [];
        foreach ($groups as $groups) {
            $gS[] = $groups->group_id;
        }
        return $gS;
    }
}
