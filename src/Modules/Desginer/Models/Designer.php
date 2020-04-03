<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Designer extends Model
{

    protected $table = 'designers';

    static function searchDesigners($q = 'a', $count = 15)
    {
        $designers = Designer::where("brand_name", 'LIKE', "%$q%")
            ->orWhereRaw('LOWER(`brand_name`)  like ?', array("%" . $q . "%"));
        if ($count > 0) {
            return $designers->paginate($count);
        } else {
            return $designers->count();
        }
    }
}
