<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Post;
use League\Fractal;

class PostsResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;

    public function transform(Post $data)
    {
        $this->data = $data;
        $this->setDefaultData();
        $this->setPostImg();
        $this->setPostVideo();
        $this->userData();
        $this->friendData();
        $this->groupData();
        $this->likersData();
        $this->setVoteData();

        return $this->return;
    }

    private function setDefaultData()
    {
        $this->return = [
            'type' => 'post',
            'comments_count' => isset($this->data->comments) ? count($this->data->comments) : 0,
            'comments' => [],
            'total_share_count' => 0,
            'shares_count' =>  isset($this->data->shares) ? count($this->data->shares) : 0,
            'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($this->data->created_at),
            'post' => isset($this->data->post_id) ? $this->data->post_id : 0,
            'color' => isset($this->data->color) ? json_decode($this->data->color) : NULL,
            'groupId' => isset($this->data->group_id) ? $this->data->group_id : 0,
            'friendId' => isset($this->data->friend_id) ? $this->data->friend_id : NULL,
            'content' => isset($this->data->content) ? $this->data->content : NULL,
            'subject' => isset($this->data->subject) ? $this->data->subject : NULL,
            'mentions' => isset($this->data->mentions) ? unserialize($this->data->mentions) : NULL,


        ];
    }

    private function setVoteData()
    {
        $votes = $this->data->choices;
        $dataVotes = [];
        $userData = app('session')->get('tempID');

        $isUserVoted = false;
        if ($votes) {
            foreach ($votes as $vote) {
                foreach ($vote->usersVote as $voted) {
                    if (!$isUserVoted && (isset($voted->user_id))) {

                        $isUserVoted =  $voted->user_id == $userData  ? true : false;
                    }
                }
                $newRow = array(
                    'id'            => $vote->id,
                    'type'          => $vote->type,
                    'content'       => $vote->option,
                    'total'         => count($vote->usersVote),
                    'isUserVoted'   => $isUserVoted,
                    'endDate'       => $vote->end_date > \Carbon\Carbon::now() ?  \Carbon\Carbon::now()->diffInHours($vote->end_date)-3 : 0
                );
                $item = false;
                if ($vote->type == 'store') {
                    $item = (new \OlaHub\UserPortal\Models\CatalogItem)->where('item_slug', $vote->option)->first();
                } else if ($vote->type == 'designer') {
                    $item = (new \OlaHub\UserPortal\Models\DesignerItems)->where('item_slug', $vote->option)->first();
                }
                if ($item) {
                    $newRow['item_img'] = isset($item->images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($item->images[0]) : NULL;
                    $newRow['item_title'] = $item->name;
                }
                $dataVotes[] = $newRow;
            }
        }
        $x = 0;
        foreach ($dataVotes as $total) {
            $x += $total['total'];
        }
        $this->return['isUserVoted'] = $isUserVoted;
        $this->return['totalCountVote'] = $x;
        $this->return['votes'] = $dataVotes;
    }

    private function setPostImg()
    {
        $finalPath = NULL;
        if (!empty($this->data->post_images)) {
            $imgs = explode(",", $this->data->post_images);
            $path = [];
            foreach ($imgs as $img) {
                $imagePath = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($img);
                array_push($path, $imagePath);
            }
            $finalPath = $path;
        }
        $this->return['post_img'] = $finalPath;
    }

    private function setPostVideo()
    {
        $finalPath = NULL;
        if (!empty($this->data->post_videos)) {
            $videos = explode(",", $this->data->post_videos);
            $path = [];
            foreach ($videos as $video) {
                $imagePath = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($video);
                array_push($path, $imagePath);
            }
            $finalPath = $path;
        }
        $this->return['post_video'] = $finalPath;
    }

    private function userData()
    {
        $author = $this->data->author;
        $authorName = $author['first_name']. $author['last_name'];
        $this->return['user_info'] = [
            'user_id' => $author['id'],
            'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($author['profile_picture']),
            'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($author, 'profile_url', $authorName, '.'),
            'username' => $authorName,
        ];
    }

    private function friendData()
    {
        if ($this->data->friend_id) {
            $friend = \OlaHub\UserPortal\Models\UserModel::find($this->data->friend_id);
            $this->return['friend_info'] = [
                'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($friend->profile_picture),
                'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($friend, 'profile_url', "$friend->first_name $friend->last_name", '.'),
                'username' => "$friend->first_name $friend->last_name",
                'user_id' => $friend->id
            ];
        } else {
            $this->return['friend_info'] = NULL;
        }
    }
    private function groupData()
    {
        if ($this->data->group_id) {
            $group = $this->data->groupData;
            $this->return['group_title'] = $group->name;
            $this->return['groupId'] = $group->slug;
        }
    }
    private function likersData()
    {
        $liked = false;
        $likes = isset($this->data->likes) ? $this->data->likes : [];
        $likerData = [];
        foreach ($likes as $like) {
            if ($like->user_id == app('session')->get('tempID'))
                $liked = true;
            $userData = $like->author;
            $likerData[] = [
                'likerPhoto' => isset($userData->profile_picture) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->profile_picture) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                'likerProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL
            ];
        }
        $this->return['likers_count'] = isset($likes) ? count($likes) : 0;
        $this->return['liked'] = $liked;
        $this->return['likersData'] = $likerData;
    }
}
