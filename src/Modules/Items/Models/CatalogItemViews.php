<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogItemViews extends Model {

    protected $table = 'catalog_items_views';

    public function itemsMainData() {
        return $this->hasMany('OlaHub\UserPortal\Models\CatalogItem', 'clasification_id');
    }
    
    static function setItemView($item){
        $request = \Illuminate\Http\Request::capture();
        $userIP = $request->ip();
        $userBrowser = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getUserBrowserAndOS($request->userAgent());
        $oldView = CatalogItemViews::where('item_id',$item->id)->where('browser_name',$userBrowser)->where('user_ip',$userIP)->first();
        if(!$oldView){
            $oldView = new CatalogItemViews;
            $oldView->item_id = $item->id;
            $oldView->browser_name = $userBrowser;
            $oldView->user_ip = $userIP;
            $oldView->save();
            $item->total_views++;
            $item->save();
        }
    }

}
