<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{

    protected $table = 'users';
    protected $connection = 'mysql';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('notTemp', function ($query) {
            $query->where(function ($q) {
                $q->where(function ($temp) {
                    $temp->where('invited_by', '>', '0');
                    $temp->whereNotNull('invitation_accepted_date');
                });
                $q->orWhere(function ($temp) {
                    $temp->where(function ($tempNull) {
                        $tempNull->where('invited_by', "0");
                        $tempNull->orWhereNull('invited_by');
                    });
                    $temp->whereNull('invitation_accepted_date');
                });
            });
        });

        static::saving(function ($model) {
            if (isset($model->password) && strlen($model->password) > 5 && strpos($model->password, 'OlaHubHashing:') === false) {
                $model->password = (new \OlaHub\UserPortal\Helpers\SecureHelper)->setPasswordHashing($model->password);
            }
        });

        static::created(function ($model) {
            $slug = (new \OlaHub\UserPortal\Helpers\UserHelper)->createProfileSlug(strtolower($model->first_name . ' ' . $model->last_name), $model->id);
            $model->profile_url = $slug;
            $model->save();
        });
    }

    static $columnsMaping = [
        'userCountry' => [
            'column' => 'country_id',
            'type' => 'int',
            'relation' => false,
            // 'validation' => 'required'
        ],
        'userFirstName' => [
            'column' => 'first_name',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|max:50'
        ],
        'userLastName' => [
            'column' => 'last_name',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|max:50'
        ],
        'userPhoneNumber' => [
            'column' => 'mobile_no',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:20'
        ],
        'userCode' => [
            'column' => 'activation_code',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:50'
        ],
        'userEmail' => [
            'column' => 'email',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:50'
            // 'validation' => 'max:50|email'
        ],
        'userBirthday' => [
            'column' => 'user_birthday',
            'type' => 'string',
            'relation' => false,
            // 'validation' => 'max:50|date'
        ],
        'userGender' => [
            'column' => 'user_gender',
            'type' => 'string',
            'relation' => false,
            // 'validation' => 'in:m,f'
        ],
        'userPassword' => [
            'column' => 'password',
            'type' => 'string',
            'relation' => false,
            'validation' => 'min:6'
        ],
        'userFacebook' => [
            'column' => 'facebook_id',
            'type' => 'string',
            'relation' => false,
            'validation' => ''
        ],
        'appleToken' => [
            'column' => 'apple_token',
            'type' => 'string',
            'relation' => false,
            'validation' => ''
        ],
        'userGoogle' => [
            'column' => 'google_id',
            'type' => 'string',
            'relation' => false,
            'validation' => ''
        ],
        'userInterests' => [
            'column' => 'interests',
            'type' => 'string',
            'relation' => false,
            'validation' => ''
        ],

    ];
    static $columnsInvitationMaping = [
        'userFirstName' => [
            'column' => 'first_name',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|max:50'
        ],
        'userLastName' => [
            'column' => 'last_name',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|max:50'
        ],
        'userPhoneNumber' => [
            'column' => 'mobile_no',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:20'
        ],
        'userEmail' => [
            'column' => 'email',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:50|email'
        ]
    ];

    public function country()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\Country');
    }

    public function shippingAddress()
    {
        return $this->hasOne('OlaHub\UserPortal\Models\UserShippingAddressModel', 'user_id');
    }

    public function calendars()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\CalendarModel', 'user_id');
    }

    public function wishLish()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\WishList', 'user_id');
    }

    public function celebrationParticipants()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\CelebrationParticipantsModel', 'user_id');
    }

    public function friends()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\Friends', 'user_id', 'id');
    }

    public function refCode()
    {
        return $this->hasOne('OlaHub\UserPortal\Models\UsersReferenceCodeUsedModel', 'user_id', 'id');
    }

    public function getColumns($requestData, $user = false)
    {
        if ($user) {
            $array = $user;
        } else {
            $array = new \stdClass;
        }

        foreach ($requestData as $key => $value) {
            if (isset(UserModel::$columnsMaping[$key]['column'])) {
                $array->{UserModel::$columnsMaping[$key]['column']} = $value;
            }
        }
        return $array;
    }

    static function searchUsers($q = 'a', $eventId = false, $groupId = false, $count = 15, $active = false)
    {
        $words = explode(" ", $q);
        $userModel = (new UserModel)->newQuery();
        // $q = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::rightPhoneNoJO($q);

        $userModel->where(function ($query) use ($words) {

            $query->where(function ($q1) use ($words) {
                foreach ($words as $word) {
                    $q1->where('first_name', $word);
                    $q1->orWhere('last_name', $word);
                    $q1->orWhere('email', $word);
                    $q1->orWhere('mobile_no', $word);
                }
            });
            $query->orWhere(function ($q2) use ($words) {
                foreach ($words as $word) {
                    $length = strlen($word);
                    if ($length >= 3) {
                        $firstWords = substr($word, 0, 3);
                        $q2->Where('first_name', 'like', '%' . $firstWords . '%');


                        if ($length >= 6) {
                            $lastWords = substr($word, -3);
                            $q2->Where('first_name', 'like', '%' . $lastWords . '%');
                        }
                    } else if ($length == 2) {
                        $q2->Where('first_name', 'like', '%' . $word . '%');
                    }
                }
            });
            $query->orWhere(function ($q2) use ($words) {
                foreach ($words as $word) {
                    $length = strlen($word);
                    if ($length >= 3) {
                        $firstWords = substr($word, 0, 3);
                        $q2->Where('last_name', 'like', '%' . $firstWords . '%');


                        if ($length >= 6) {
                            $lastWords = substr($word, -3);
                            $q2->Where('last_name', 'like', '%' . $lastWords . '%');
                        }
                    } else if ($length == 2) {
                        $q2->Where('last_name', 'like', '%' . $word . '%');
                    }
                }
            });
        })
            ->where('users.id', '<>', app('session')->get('tempID'));
        if ($eventId) {
            $userModel->whereRaw('users.id NOT IN (select user_id from celebration_participants
                     where celebration_participants.celebration_id = "' . (int) $eventId . '" )
                     and users.id NOT IN (select user_id from celebrations where celebrations.id="' . (int) $eventId . '")');
        }
        if ($groupId) {
            $members = GroupMembers::getMembersArr($groupId);
            if (count($members)) {
                $userModel->whereNotIn('id', $members);
            }
        }
        if ($active) {
            $userModel->where('is_active', '1');
        }
        if ($count > 0) {
            return $userModel->paginate($count);
        } else {
            return $userModel->count();
        }
    }
    //$userModel->where(function ($query) use ($q) {
    //            $query->whereRaw('LOWER(`email`) like ?', "%" . $q . "%")
    //                ->orWhere("users.mobile_no", 'like', "%" . $q . "%")
    //                ->orWhereRaw("concat(LOWER(`first_name`), ' ', LOWER(`last_name`)) like ?", "%" . $q . "%");
    //        })->where('users.id', '<>', app('session')->get('tempID'));
    static function getUserSlug($user)
    {

        if ($user->profile_url && !preg_match('/^[.][.][0-9]+$/', $user->profile_url)) {
            return $user->profile_url;
        }
        $slug = (new \OlaHub\UserPortal\Helpers\UserHelper)->createProfileSlug(strtolower($user->first_name . ' ' . $user->last_name), $user->id);
        $user->profile_url = $slug;
        $saved = $user->save();
        if ($saved) {
            return $slug;
        }
        return Null;
    }

    static function checkTempUser($user)
    {
        $return = true;
        if ($user->invited_by > 0 && $user->invitation_accepted_date != NULL) {
            $return = false;
        } elseif ($user->invited_by == NULL && $user->invitation_accepted_date == NULL) {
            $return = false;
        }
        return $return;
    }

    public function catalogItemViews()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\CatalogItemViews', 'user_id');
    }

    static function checkReferenceCodeUser($code, $type)
    {
        $date = date("Y-m-d");
        $check = UsersReferenceCodeModel::where('code', $code)->where('type', $type)->first();
        if ($check) {
            if ($date > $check->end_date) {
                return "expired";
            }
            elseif ($date < $check->start_date) {
                return "notBegin";
            }
            elseif ($date >= $check->srart_date && $date <= $check->end_date) {
                return $check->id;
            }
        }
        return false;
    }
}
