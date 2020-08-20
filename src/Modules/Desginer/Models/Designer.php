<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Designer extends Model
{

    protected $table = 'designers';

    static function searchDesigners($text = 'a', $count = 15)
    {
        $words = explode(" ", $text);

        $items = Designer::where(function ($q) use($words) {

            $q->where(function ($q1) use($words) {
                foreach ($words as $word){
                    $q1->whereRaw('FIND_IN_SET(?, REPLACE(brand_name, " ", ","))', $word);
                }
            });
            $q->orWhere(function ($q3) use($words) {
                foreach ($words as $word){
                    $length = strlen($word);
                    if($length >= 3)
                    {
                        $firstWords = substr($word, 0,3);
                        $q3->whereRaw('LOWER(`brand_name`) LIKE ? ','%' . $firstWords . '%');

                        if($length >= 6){
                            $lastWords = substr($word, -3);
                            $q3->WhereRaw('LOWER(`brand_name`) LIKE ? ','%' . $lastWords . '%');
                        }
                    }else if($length == 2){
                        $q3->whereRaw('LOWER(`brand_name`) LIKE ? ','%' . $word . '%');
                    }
                }
            });
        });

        $items->orWhere('brand_name', '=', $text);

//        $designers = Designer::whereRaw('LOWER(`brand_name`) like ?', "%$q%");

        if ($count > 0) {
            return $items->paginate($count);
        } else {
            return $items->count();
        }
    }
}
