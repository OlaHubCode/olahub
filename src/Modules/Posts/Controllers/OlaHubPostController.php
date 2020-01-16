<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\Post;

class OlaHubPostController extends BaseController {

    protected $requestData;
    protected $requestFilter;
    protected $userAgent;

    public function __construct(Request $request) {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function getPosts($type = false) {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "getPosts"]);
        
        $return = ['status' => false, 'message' => 'NoData', 'code' => 204];
        if ($type && !in_array($type, ['group', 'friend'])) {
           $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'likedProductBefore', 'code' => 204]]);
           $log->saveLogSessionData();
            return ['status' => false, 'message' => 'someData', 'code' => 406, 'errorData' => []];
        }
        if ($type == 'group') {
            $postsTemp = Post::where('group_id', $this->requestData['groupId'])->where('isApprove', 1)->orderBy('created_at', 'desc')->paginate(15);
            $posts = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($postsTemp, '\OlaHub\UserPortal\ResponseHandlers\PostsResponseHandler');
            $sponsers_arr = [];
            try {
                $timelinePosts = \DB::table('campaign_slot_prices')->where('country_id', app('session')->get('def_country')->id)->where('is_post', '1')->get();
                foreach ($timelinePosts as $onePost) {
                    $sponsers = \OlaHub\Models\AdsMongo::where('slot', $onePost->id)->where('country', app('session')->get('def_country')->id)->orderBy('id', 'RAND()')->paginate(5);
                    foreach ($sponsers as $one) {
                        $campaign = \OlaHub\Models\Ads::where('campign_token', $one->token)->first();
                        $liked = 0;
                        if ($campaign) {
                            $oldLike = \OlaHub\UserPortal\Models\UserPoints::where('user_id', app('session')->get('tempID'))
                                    ->where('country_id', app('session')->get('def_country')->id)
                                    ->where('campign_id', $campaign->id)
                                    ->first();
                            if ($oldLike) {
                                $liked = 1;
                            }
                        }

                        $sponsers_arr[] = [
                            'type' => 'sponser',
                            "adToken" => isset($one->token) ? $one->token : NULL,
                            'updated_at' => isset($one->updated_at) ? $one->updated_at : 0,
                            'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($one->created_at),
                            'post' => isset($one->_id) ? $one->_id : 0,
                            "adSlot" => isset($one->slot) ? $one->slot : 0,
                            "adRef" => isset($one->content_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($one->content_ref) : NULL,
                            "adText" => isset($one->content_text) ? $one->content_text : NULL,
                            "adLink" => isset($one->access_link) ? $one->access_link : NULL,
                            "liked" => $liked,
                        ];
                    }
                }
            } catch (Exception $ex) {
                
            }

            if ($postsTemp->count() > 0) {
                // shuffle($timeline);
                $all = [];
                $count_timeline = $postsTemp->count();
                $count_sponsers = count($sponsers_arr);
                $break = $count_sponsers > 0 ? (int) ($count_timeline / $count_sponsers - 1) : 0;
                $start_in = 0;
                for ($i = 0; $i < $postsTemp->count(); $i++) {
                    $all[] = $posts["data"][$i];
                    if ($break - 1 == $i) {
                        if (isset($sponsers_arr[$start_in])) {
                            $all[] = $sponsers_arr[$start_in];
                            $start_in++;
                            $break = $break * 2;
                        }
                    }
                }
                $return = ['status' => true, 'data' => $all, 'meta' => isset($posts["meta"]) ? $posts["meta"] : [], 'code' => 200];
            }
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
            return response($return, 200);
        } elseif ($type == 'friend') {
            $posts = Post::where('user_id', (int) $this->requestData['userId'])->where('privacy', 3)->where('isApprove', 1)->orderBy('created_at', 'desc')->paginate(10);
        } else {
            $userID = app('session')->get('tempID');
            $posts = Post::where('user_id', $userID)->where('isApprove', 1)->orderBy('created_at', 'desc')->paginate(10);
        }
        if ($posts->count() > 0) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($posts, '\OlaHub\UserPortal\ResponseHandlers\PostsResponseHandler');
            $return['status'] = TRUE;
            $return['code'] = 200;
        }
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getOnePost() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "getOnePost"]);
        
        $return = ['status' => false, 'msg' => 'NoData', 'code' => 204];
        if (isset($this->requestData['postId']) && $this->requestData['postId']) {
            $post = Post::where('_id', $this->requestData['postId'])->first();

            if ($post) {
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($post, '\OlaHub\UserPortal\ResponseHandlers\PostsResponseHandler');
                $return['status'] = TRUE;
                $return['code'] = 200;
            }
        }
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
        return response($return, 200);
    }

    public function addNewPost() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "addNewPost"]);
        
        $return = ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []];
        if (count($this->requestData) > 0 && TRUE /* \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(Post::$columnsMaping, $this->requestData) */) {
            $postMongo = new Post;
            $postMongo->user_id = app('session')->get('tempID');
            $groupData = NULL;
            if (isset($this->requestData['group']) && $this->requestData['group']) {
                $groupData = \OlaHub\UserPortal\Models\groups::where('_id', $this->requestData["group"])->first();
                $postMongo->group_id = $this->requestData['group'];
                $postMongo->privacy = $groupData->privacy;
                $postMongo->group_title = $groupData->name;
                if ($groupData->posts_approve && $groupData->creator != app('session')->get('tempID')) {
                    $postMongo->isApprove = 0;
                } else {
                    $postMongo->isApprove = 1;
                }
            } else {
                $postMongo->group_id = NULL;
                $postMongo->privacy = 3;
                $postMongo->group_title = NULL;
                $postMongo->isApprove = 1;
            }
            $postMongo->likes = [];
            $postMongo->shares = [];
            $postMongo->comments = [];
            $postMongo->commenters = [app('session')->get('tempID')];
            $postMongo->post = isset($this->requestData['content']) ? $this->requestData['content'] : NULL;
            $postMongo->subject = isset($this->requestData['subject']) ? $this->requestData['subject'] : NULL;
            $postMongo->type = 'post';
            if ($this->requestData['post_file'] && count($this->requestData['post_file']) > 0) {
                $postImage = [];
                foreach ($this->requestData['post_file'] as $image) {
                    if (isset($this->requestData['group']) && $this->requestData['group']) {
                        $file = \OlaHub\UserPortal\Helpers\GeneralHelper::moveImage($image, 'posts/' . $this->requestData['group']);
                    } else {
                        $file = \OlaHub\UserPortal\Helpers\GeneralHelper::moveImage($image, 'posts/' . app('session')->get('tempID'));
                    }
                    array_push($postImage, $file);
                }
                $postMongo->post_image = $postImage;
            }
            if ($this->requestData['post_video'] && count($this->requestData['post_video']) > 0) {
                $postVideo = [];
                foreach ($this->requestData['post_video'] as $video) {
                    if (isset($this->requestData['group']) && $this->requestData['group']) {
                        $fileVideo = \OlaHub\UserPortal\Helpers\GeneralHelper::moveImage($video, 'posts/' . $this->requestData['group']);
                    } else {
                        $fileVideo = \OlaHub\UserPortal\Helpers\GeneralHelper::moveImage($video, 'posts/' . app('session')->get('tempID'));
                    }
                    array_push($postVideo, $fileVideo);
                }
                $postMongo->post_video = $postVideo;
            }
            if (isset($this->requestData['group']) && $this->requestData['group']) {
                $group = $groupData;
                if ($group->posts_approve && $group->creator != app('session')->get('tempID')) {
                    $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                    $notification->type = 'group';
                    $notification->content = "notifi_postGroup";
                    $notification->user_name = "";
                    $notification->community_title = $group->name;
                    $notification->group_id = $this->requestData['group'];
                    $notification->avatar_url = $group->avatar_url;
                    $notification->read = 0;
                    $notification->for_user = $group->creator;
                    $notification->save();
                } else {
                    foreach ($group->members as $member) {
                        if ($member != app('session')->get('tempID')) {

                            $existNotifi = \OlaHub\UserPortal\Models\NotificationMongo::where('for_user', $member)->where('content', 'notifi_postGroup')->where('read', 0)->first();
                            if ($existNotifi) {
                                continue;
                            } else {
                                $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                                $notification->type = 'group';
                                $notification->content = "notifi_postGroup";
                                $notification->user_name = "";
                                $notification->community_title = $group->name;
                                $notification->group_id = $this->requestData['group'];
                                $notification->avatar_url = $group->avatar_url;
                                $notification->read = 0;
                                $notification->for_user = $member;
                                $notification->save();
                            }
                        }
                    }
                }
            }
            $postMongo->save();
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($postMongo, '\OlaHub\UserPortal\ResponseHandlers\PostsResponseHandler');
            $return['status'] = TRUE;
            $return['code'] = 200;
        }
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
        return response($return, 200);
    }

    public function addNewComment() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "addNewComment"]);
        
        $return = ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []];
        if (count($this->requestData) > 0 && TRUE /* \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(Post::$columnsMaping, $this->requestData) */) {
            $postID = $this->requestData['post_id'];
            $comment = $this->requestData['content']['comment'];
            $postMongo = Post::find($postID);
            if ($postMongo) {
                $commentData = [
                    'comment_id' => count($postMongo->comments),
                    'user_id' => app('session')->get('tempID'),
                    'comment' => $comment,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $postMongo->push('comments', $commentData, true);
                $postMongo->push('commenters', app('session')->get('tempID'), true);
                $author = app('session')->get('tempData');
                $authorName = "$author->first_name $author->last_name";
                $commentData['post'] = $postID;
                $commentData['time'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($commentData['created_at']);
                $commentData['user_info'] = [
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($author->profile_picture),
                    'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($author, 'profile_url', $authorName, '.'),
                    'username' => $authorName,
                ];
                $return['data'] = $commentData;
                $return['status'] = TRUE;
                $return['code'] = 200;

                if ($postMongo->user_id != app('session')->get('tempID')) {
                    $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                    $notification->type = 'post';
                    $notification->content = "notifi_comment";
                    $notification->user_name = $authorName;
                    $notification->post_id = $postID;
                    $notification->avatar_url = $author->profile_picture;
                    $notification->read = 0;
                    $notification->for_user = $postMongo->user_id;
                    $notification->save();
                }
            }
        }
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getPostComments() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "getPostComments"]);
        
        if (isset($this->requestData['postId']) && $this->requestData['postId']) {
            $post = Post::where('_id', $this->requestData['postId'])->first();
            if ($post) {
                if (isset($post->comments) && count($post->comments) > 0) {
                    $return = [];
                    foreach ($post->comments as $comment) {
                        $userData = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $comment['user_id'])->first();
                        $repliesData = [];
                        if (isset($comment['replies']) && $comment['replies']) {
                            foreach ($comment['replies'] as $reply) {
                                $userReplyData = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $reply['user_id'])->first();
                                $repliesData[] = [
                                    'reply_id' => $reply['reply_id'],
                                    'user_id' => $reply['user_id'],
                                    'reply' => $reply['reply'],
                                    'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($reply['created_at']),
                                    'user_info' => [
                                        'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userReplyData->avatar_url),
                                        'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($userReplyData, 'profile_url', $userReplyData->username, '.'),
                                        'username' => $userReplyData->username,
                                    ]
                                ];
                            }
                        }
                        $return["data"][] = [
                            'comment_id' => $comment['comment_id'],
                            'user_id' => $comment['user_id'],
                            'comment' => $comment['comment'],
                            'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($comment['created_at']),
                            'user_info' => [
                                'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url),
                                'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($userData, 'profile_url', $userData->username, '.'),
                                'username' => $userData->username,
                            ],
                            'replies' => $repliesData
                        ];
                    }
                    $return["status"] = true;
                    $return["code"] = 200;
                    $log->setLogSessionData(['response' => $return]);
                    $log->saveLogSessionData();
                    return response($return, 200);
                }
                $return = ['status' => false, 'msg' => 'NoComments', 'code' => 204];
            }
        }
        $return = ['status' => false, 'msg' => 'NoData', 'code' => 204];
    }

    public function addNewReply() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "addNewReply"]);
        
        $return = ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []];
        if (count($this->requestData) > 0 && TRUE /* \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(Post::$columnsMaping, $this->requestData) */) {
            $postID = $this->requestData['post_id'];
            $reply = $this->requestData['content']['reply'];
            $postMongo = Post::find($postID);
            if ($postMongo) {
                foreach ($postMongo->comments as $comment) {
                    if ($comment["comment_id"] == $this->requestData['comment_id']) {
                        $replyData = [
                            'reply_id' => isset($comment['replies']) ? count($comment['replies']) : 0,
                            'user_id' => app('session')->get('tempID'),
                            'reply' => $reply,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                        $postMongo->pull('comments', $comment);
                        $comment["replies"][] = $replyData;
                        $postMongo->push('comments', $comment);
                        if ($comment['user_id'] != app('session')->get('tempID')) {
                            $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                            $notification->type = 'post';
                            $notification->content = "notifi_reply";
                            $notification->user_name = app('session')->get('tempData')->first_name . ' ' . app('session')->get('tempData')->last_name;
                            $notification->post_id = $postID;
                            $notification->avatar_url = app('session')->get('tempData')->profile_picture;
                            $notification->read = 0;
                            $notification->for_user = $comment['user_id'];
                            $notification->save();
                        }


                        if ($postMongo->user_id != app('session')->get('tempID')) {
                            $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                            $notification->type = 'post';
                            $notification->content = "notifi_comment";
                            $notification->user_name = app('session')->get('tempData')->first_name . ' ' . app('session')->get('tempData')->last_name;
                            $notification->post_id = $postID;
                            $notification->avatar_url = app('session')->get('tempData')->profile_picture;
                            $notification->read = 0;
                            $notification->for_user = $postMongo->user_id;
                            $notification->save();
                        }
                    }
                }

                $postMongo->push('commenters', app('session')->get('tempID'), true);

                $author = app('session')->get('tempData');
                $authorName = "$author->first_name $author->last_name";
                $replyData['post'] = $postID;
                $replyData['time'] = date('Y-m-d H:i:s');
                $replyData['user_info'] = [
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($author->profile_picture),
                    'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($author, 'profile_url', $authorName, '.'),
                    'username' => $authorName,
                ];
                $return['data'] = $replyData;
                $return['status'] = TRUE;
                $return['code'] = 200;
            }
        }
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function deletePost() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "addNewReply"]);
        
        if (empty($this->requestData['postId'])) {
             $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
             $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        $post = Post::find($this->requestData['postId']);
        if ($post) {
            if ($post->user_id != app('session')->get('tempID')) {
                if (isset($post->group_id) && $post->group_id > 0) {
                    $group = \OlaHub\UserPortal\Models\groups::where('creator', app('session')->get('tempID'))->find($post->group_id);
                    if (!$group) {
                         $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'Not allow to delete this post', 'code' => 400]]);
                         $log->saveLogSessionData();
                        return response(['status' => false, 'msg' => 'Not allow to delete this post', 'code' => 400], 200);
                    }
                } else {
                     $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'Not allow to delete this post', 'code' => 400]]);
                     $log->saveLogSessionData();
                    return response(['status' => false, 'msg' => 'Not allow to delete this post', 'code' => 400], 200);
                }
            }
            $post->delete = 1;
            $post->save();
             $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'You delete post successfully', 'code' => 200]]);
             $log->saveLogSessionData();
            return response(['status' => true, 'msg' => 'You delete post successfully', 'code' => 200], 200);
        }
         $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
          $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function updatePost() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "addNewReply"]);
        
        if (empty($this->requestData['postId'])) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
          $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        if (isset($this->requestData['content']) && !$this->requestData['content'] && isset($this->requestData['subject']) && !$this->requestData['subject']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => ['content' => ['validation.required']]]]);
          $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => ['content' => ['validation.required']]], 200);
        }
        $post = Post::find($this->requestData['postId']);
        if ($post) {
            if ($post->user_id != app('session')->get('tempID')) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'Not allow to edit this post', 'code' => 400]]);
          $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'Not allow to edit this post', 'code' => 400], 200);
            }
            if ($post->post != $this->requestData['content']) {
                $post->push('history', [date("Y-m-d H:i:s") => $post->post], true);
            }
            if ($post->subject != $this->requestData['subject']) {
                $post->push('history', [date("Y-m-d H:i:s") => $post->subject], true);
            }

            $post->post = isset($this->requestData['content']) ? $this->requestData['content'] : NULL;
            $post->subject = isset($this->requestData['subject']) ? $this->requestData['subject'] : NULL;
            $post->save();
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($post, '\OlaHub\UserPortal\ResponseHandlers\PostsResponseHandler');
            $return['status'] = TRUE;
            $return['code'] = 200;
            $log->setLogSessionData(['response' => $return]);
          $log->saveLogSessionData();
            return response($return, 200);
        }
          $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
          $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

}
