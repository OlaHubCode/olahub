<?php

namespace OlaHub\UserPortal\Models;
use Illuminate\Database\Eloquent\Model;

class Designer extends Model {

    protected $table = 'designers';
    
    static function searchDesigners($q = 'a', $count = 15) {
        $designers = DesignerItems::where("brand_name", 'LIKE', "%$q%");
        if ($count > 0) {
            $designers = $designers->paginate($count);
            $designersId = [];
            foreach ($designers as $des){
                $designersId[] = $des->designer_id;
            }
            return Designer::whereIn('id', $designersId)->get();
        }else{
            return $designers->count();
        }
    }

}
