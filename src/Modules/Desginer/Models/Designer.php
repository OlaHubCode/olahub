<?php

namespace OlaHub\UserPortal\Models;
use Illuminate\Database\Eloquent\Model;

class Designer extends Model {

    protected $table = 'designers';
    
    static function searchDesigners($q = 'a', $count = 15) {
        $designers = DesginerItems::where("designer_name", 'LIKE', "%$q%");
        if ($count > 0) {
            $designersMongo = $designers->paginate($count);
            $designersId = [];
            foreach ($designersMongo as $des){
                $designersId[] = $des->designer_id;
            }
            return Designer::whereIn('id', $designersId)->get();
        }else{
            return $designers->count();
        }
    }

}
