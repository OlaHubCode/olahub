<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Post;
use League\Fractal;

class PostsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(Post $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setPostImg();
        $this->setPostVideo();
        $this->likersData();
        $this->userData();
        return $this->return;
    }

    private function setDefaultData() {
        $liked = 0;
        if (in_array(app('session')->get('tempID'), $this->data->likes)) {
            $liked = 1;
        }
        $this->return = [
            'type' => 'post',
            'comments_count' => isset($this->data->comments) ? count($this->data->comments) : 0,
            'comments' => [],
            'total_share_count' => isset($this->data->shares) ? count($this->data->shares) : 0,
            'likers_count' => isset($this->data->likes) ? count($this->data->likes) : 0,
            'liked' => $liked,
            'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($this->data->created_at),
            'post' => isset($this->data->_id) ? $this->data->_id : 0,
            'groupId' => isset($this->data->group_id) ? $this->data->group_id : 0,
            'content' => isset($this->data->post) ? $this->data->post : NULL,
            'subject' => isset($this->data->subject) ? $this->data->subject : NULL,
            'privacy' => isset($this->data->privacy) ? $this->data->privacy : NULL,
            'group_title' => isset($this->data->group_title) ? $this->data->group_title : NULL,
            'isApprove' => isset($this->data->isApprove) ? $this->data->isApprove : 0,
        ];
    }

    private function setPostImg() {
        $finalPath = NULL;
        if (is_array($this->data->post_image)) {
            if ($this->data->post_image && count($this->data->post_image) > 0) {
                $path = [];
                foreach ($this->data->post_image as $image) {
                    $imagePath = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image);
                    array_push($path, $imagePath);
                }
                $finalPath = $path;
            }
        } else {
            if ($this->data->post_image) {
                $finalPath[] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->post_image);
            }
        }

        $this->return['post_img'] = $finalPath;
    }

    private function setPostVideo() {
        $finalPath = NULL;
        if (is_array($this->data->post_video)) {
            if ($this->data->post_video && count($this->data->post_video) > 0) {
                $path = [];
                foreach ($this->data->post_video as $video) {
                    $videoPath = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($video);
                    array_push($path, $videoPath);
                }
                $finalPath = $path;
            }
        } else {
            if ($this->data->post_vide) {
                $finalPath[] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->post_video);
            }
        }

        $this->return['post_video'] = $finalPath;
    }

    private function userData() {
        $author = $this->data->author;
        $authorName = "$author->first_name $author->last_name";
        $this->return['user_info'] = [
            'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($author->profile_picture),
            'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($author, 'profile_url', $authorName, '.'),
            'username' => $authorName,
        ];
    }

    private function likersData() {
        $likes = isset($this->data->likes) ? $this->data->likes : [];
        $likerData = [];
        foreach ($likes as $like) {
            $userData = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $like)->first();
            $likerData [] = [
                'likerPhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                'likerProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL
            ];
        }
        $this->return['likersData'] = $likerData;
    }

}
