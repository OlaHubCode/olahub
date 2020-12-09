<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class groups extends Model
{

    protected $table = 'groups';
    static $columnsMaping = [
        'groupPrivacy' => [
            'column' => 'privacy',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|in:1,2,3'
        ],
        'groupName' => [
            'column' => 'name',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required'
        ],
        'groupDescription' => [
            'column' => 'description',
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
        'groupInterests' => [
            'column' => 'interests',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|array|max:2'
        ],
        'groupPostApprove' => [
            'column' => 'posts_approve',
            'type' => 'string',
            'relation' => false,
            'validation' => 'in:1,0'
        ],
        'onlyMyStores' => [
            'column' => 'only_my_stores',
            'type' => 'string',
            'relation' => false,
            'validation' => 'boolean'
        ],
    ];

    public function members()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\GroupMembers', 'group_id');
    }

    static function searchGroups($text = 'a', $count = 15)
    {
        $words = explode(" ", $text);
        $items = groups::whereIn("privacy", [2, 3]);
        $items->where(function ($q) use ($words, $text) {

            $q->where(function ($q1) use ($words) {
                foreach ($words as $word) {
                    $q1->whereRaw('FIND_IN_SET(?, REPLACE(description, " ", ","))', $word);
                }
            });
            $q->orWhere(function ($q2) use ($words) {
                foreach ($words as $word) {
                    $q2->whereRaw('FIND_IN_SET(?, REPLACE(name, " ", ","))', $word);
                }
            });
            $q->orWhere(function ($q2) use ($text) {
                $q2->Where('description', '=', $text);
            });
            $q->orWhere(function ($q2) use ($text) {
                $q2->Where('name', '=', $text);
            });
        });

        // $items->orWhere('description', '=', $text);
        // $items->orWhere('name', '=', $text);
        if ($count > 0) {
            return $items->paginate($count);
        } else {
            return $items->get()->count();
        }
    }
}
