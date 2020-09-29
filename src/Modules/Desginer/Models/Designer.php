<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Designer extends Model
{

    protected $table = 'designers';

    public function itemsMainData()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\DesignerItems', 'designer_id');
    }

    static function getDesignerBySlug($slug)
    {
        $return['mainBanner'] = [\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'shop_banner')];
        $return['storeData']['storeLogo'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        $designer = Designer::where('designer_slug', $slug)->first();
        if ($designer) {
            $follow = \OlaHub\UserPortal\Models\Following::where("user_id", app('session')->get('tempID'))
                ->where('target_id', $designer->id)
                ->where('type', 2)->first();
            $return['id'] = $designer->id;
            $return['mainBanner'] = [\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($designer->banner_image_ref, 'shop_banner')];
            $return['storeName'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($designer, 'name');
            $return['storeData']['storeLogo'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($designer->logo_ref);
            $return['followed'] = isset($follow) ? true : false;
        }
        return $return;
    }
    static function searchDesigners($text = 'a', $count = 15)
    {
        $words = explode(" ", $text);

        $items = Designer::where(function ($q) use ($words) {

            $q->where(function ($q1) use ($words) {
                foreach ($words as $word) {
                    $q1->whereRaw('FIND_IN_SET(?, REPLACE(brand_name, " ", ","))', $word);
                }
            });
            $q->orWhere(function ($q3) use ($words) {
                foreach ($words as $word) {
                    $length = strlen($word);
                    if ($length >= 3) {
                        $firstWords = substr($word, 0, 3);
                        $q3->whereRaw('LOWER(`brand_name`) LIKE ? ', '%' . $firstWords . '%');

                        if ($length >= 6) {
                            $lastWords = substr($word, -3);
                            $q3->WhereRaw('LOWER(`brand_name`) LIKE ? ', '%' . $lastWords . '%');
                        }
                    } else if ($length == 2) {
                        $q3->whereRaw('LOWER(`brand_name`) LIKE ? ', '%' . $word . '%');
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
