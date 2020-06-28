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

    static function searchGroups($q = 'a', $count = 15)
    {
        $groupsModel = (new groups)->newQuery();
        $groupsModel->where(function ($query) use ($q) {
            $query->whereRaw('LOWER(`name`)  like ?', "%$q%")
                ->orWhereRaw('LOWER(`description`)  like ?', "%$q%");
        })->whereIn("privacy", [2, 3]);


        if ($count > 0) {
            return $groupsModel->paginate($count);
        } else {
            return $groupsModel->get()->count();
        }
    }
}
