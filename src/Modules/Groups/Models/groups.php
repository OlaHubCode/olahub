<?php

namespace OlaHub\UserPortal\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class groups extends Eloquent {

    protected $connection = 'mongo';
    protected $collection = 'groups';
    static $columnsMaping = [
        'groupName' => [
            'column' => 'name',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required'
        ],
        'groupDescription' => [
            'column' => 'group_desc',
            'type' => 'string',
            'relation' => false,
            'validation' => ''
        ],
        'groupImage' => [
            'column' => 'image',
            'type' => 'string',
            'relation' => false,
            'validation' => ''
        ],
        'groupCover' => [
            'column' => 'cover',
            'type' => 'string',
            'relation' => false,
            'validation' => ''
        ],
        'groupPrivacy' => [
            'column' => 'privacy',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|in:1,2,3'
        ],
        'groupPostApprove' => [
            'column' => 'posts_approve',
            'type' => 'string',
            'relation' => false,
            'validation' => 'in:1,0'
        ],
        'onlyMyStores' => [
            'column' => 'onlyMyStores',
            'type' => 'string',
            'relation' => false,
            'validation' => 'boolean'
        ],
        'groupInterests' => [
            'column' => 'interests',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|array|max:2'
        ],
    ];

    static function searchGroups($q = 'a', $count = 15, $groupInterests = []) {
        $interests = [];
        foreach ($groupInterests as $oneInterest) {
            $interests[] = (int) $oneInterest->interest_id;
        }
        if (count($interests) > 0) {
            $groups = groups::where(function($query) use($q, $interests) {
                        $query->whereIn("interests", $interests)->orWhere("name", 'LIKE', "%$q%");
                    })->whereIn("privacy", [2, 3]);
        } else {
            $groups = groups::where("name", 'LIKE', "%$q%")
                            ->whereIn("privacy", [2, 3]);
        }
        if ($count > 0) {
            return $groups->paginate($count);
        } else {
            return $groups->get()->count();
        }
    }

}
