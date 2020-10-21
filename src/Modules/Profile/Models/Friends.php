<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Friends extends Model
{

    protected $table = 'users_friends';

    static function getFriend($id1, $id2)
    {
        return Friends::whereRaw("user_id = $id1 and  friend_id = $id2")->orWhereRaw("friend_id = $id1 and  user_id = $id2")->first();
    }

    static function getFriends($id)
    {
        $friends = Friends::getFriendsList($id);
        return \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope("notTemp")->whereIn('id', $friends);
    }

    static function getFriendsList($id)
    {
        $friends = Friends::where('user_id', $id)->orWhere('friend_id', $id)->get();
        $filterd = [];
        if (count($friends)) {
            foreach ($friends as $friend) {
                if ($friend->user_id != $id && !in_array($friend->user_id, $filterd) && $friend->status == 1)
                    array_push($filterd, $friend->user_id);
                if ($friend->friend_id != $id && !in_array($friend->friend_id, $filterd) && $friend->status == 1)
                    array_push($filterd, $friend->friend_id);
            }
        }
        return $filterd;
    }
    static function getAllSentRequest($id)
    {
        $friends = Friends::where('user_id', $id)->orWhere('friend_id', $id)->get();
        $filterd = [];
        if (count($friends)) {
            foreach ($friends as $friend) {
                if ($friend->user_id != $id && !in_array($friend->user_id, $filterd) && $friend->status == 2)
                    array_push($filterd, $friend->user_id);
                if ($friend->friend_id != $id && !in_array($friend->friend_id, $filterd) && $friend->status == 2)
                    array_push($filterd, $friend->friend_id);
            }
        }
        return $filterd;
    }
    static function getFriendsRequest($id)
    {
        $myRequest = Friends::where('friend_id', $id)->where('status', 2)->count();

        return $myRequest;
    }
    static function getAllblocked($id)
    {
        $friends = Friends::where('user_id', $id)->orWhere('friend_id', $id)->get();
        $filterd = [];
        if (count($friends)) {
            foreach ($friends as $friend) {
                if ($friend->user_id != $id && !in_array($friend->user_id, $filterd) && $friend->status == 3)
                    array_push($filterd, $friend->user_id);
                if ($friend->friend_id != $id && !in_array($friend->friend_id, $filterd) && $friend->status == 3)
                    array_push($filterd, $friend->friend_id);
            }
        }
        return $filterd;
    }
    static function checkStatus($id, $userID)
    {
        $status = Friends::where('user_id', $id)->Where('friend_id', $userID)->first();
        if (!$status)
            $status = Friends::where('user_id', $userID)->Where('friend_id', $id)->first();
        if (!$status)
            return 0;
        if ($status->status == 1)
            return 1; //friends
        if ($status->user_id == $id)
            return 2; // $id sent request to $userID
        else
            return 3; // $userID sent request to $id
    }
}
