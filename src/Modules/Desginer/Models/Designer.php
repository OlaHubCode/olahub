<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Designer extends Model
{

    protected $table = 'designers';

    static function searchDesigners($q = 'a', $count = 15)
    {
        $designers = Designer::whereRaw('LOWER(`brand_name`) like ?', "%$q%");
        if ($count > 0) {
            return $designers->paginate($count);
        } else {
            return $designers->count();
        }
    }
}
