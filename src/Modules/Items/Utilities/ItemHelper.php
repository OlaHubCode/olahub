<?php

namespace OlaHub\UserPortal\Helpers;

class ItemHelper extends OlaHubCommonHelper {

    function createItemPost($itemSlug, $returnData = true) {
        $post = \OlaHub\UserPortal\Models\Post::where('item_slug', $itemSlug)->where('type', 'item_post')->first();
        if(!$post){
              $item = \OlaHub\UserPortal\Models\CatalogItem::where('item_slug', $itemSlug)->first();
              $storeData = \OlaHub\UserPortal\Models\ItemStore::where('id', $item->store_id)->first();
              $images = $item->images;
                if ($images->count() > 0) {
                        $itemImage = $images[0]->content_ref;
                } else {
                    $itemImage = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
                }
              $post = new \OlaHub\UserPortal\Models\Post();
              $post->item_name = $item->name;
              $post->likes = [];
              $post->shares = [];
              $post->comments = [];
              $post->followers = [];
              $post->item_slug = $item->item_slug;
              $post->item_description = $item->description;
              $post->type = 'item_post';
              $post->country_id = $item->country_id;
              $post->post_image = $itemImage;
              $post->store_name = $storeData->name;
              $post->store_slug = $storeData->store_slug;
              $post->store_logo = $storeData->image_ref;
              $post->save();
        }
		
		if ($post && $returnData) {
            return $post;
        }elseif ($post && !$returnData) {
            return true;
        } else {
            return false;
        }
    }

}
