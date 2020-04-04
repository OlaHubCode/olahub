<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\Post;
use OlaHub\UserPortal\Models\PostComments;
use OlaHub\UserPortal\Models\PostReplies;

class OlaHubPostController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    protected $userAgent;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function getPosts($type = false)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "getPosts"]);

        $return = ['status' => false, 'message' => 'NoData', 'code' => 204];
        if ($type && !in_array($type, ['group', 'friend'])) {
            $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'likedProductBefore', 'code' => 204]]);
            $log->saveLogSessionData();
            return ['status' => false, 'message' => 'someData', 'code' => 406, 'errorData' => []];
        }
        if ($type == 'group') {
            $postsTemp = Post::where('group_id', $this->requestData['groupId'])->where('is_approve', 1)->orderBy('created_at', 'desc')->paginate(15);
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
            $friednId = (int) $this->requestData['userId'];
            $posts = Post::where(function ($q) use ($friednId) {
                $q->where(function ($userPost) use ($friednId) {
                    $userPost->where('user_id', $friednId);
                    $userPost->where('friend_id', NULL);
                });
                $q->orWhere(function ($userPost) use ($friednId) {
                    $userPost->where('friend_id', $friednId);
                });
            })->where('is_approve', 1)
                ->orderBy('created_at', 'desc')
                ->whereNull('group_id')
                ->paginate(20);
        } else {
            $userID = app('session')->get('tempID');
            $posts = Post::where(function ($q) use ($userID) {
                $q->where(function ($userPost) use ($userID) {
                    $userPost->where('user_id', $userID);
                    $userPost->where('friend_id', NULL);
                });
                $q->orWhere(function ($userPost) use ($userID) {
                    $userPost->where('friend_id', $userID);
                });
            })->where('is_approve', 1)
                ->orderBy('created_at', 'desc')
                ->paginate(20);
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

    public function getOnePost()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "getOnePost"]);

        $return = ['status' => false, 'msg' => 'NoData', 'code' => 204];
        if (isset($this->requestData['postId']) && $this->requestData['postId']) {
            $post = Post::where('post_id', $this->requestData['postId'])->first();

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

    public function addNewPost()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "addNewPost"]);

        $return = ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []];
        if (count($this->requestData) > 0 && TRUE /* \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(Post::$columnsMaping, $this->requestData) */) {
            $post = new Post;
            $post->user_id = app('session')->get('tempID');
            $post->post_id = uniqid(app('session')->get('tempID'));
            $post->content = isset($this->requestData['content']) ? $this->requestData['content'] : NULL;
            $post->color = isset($this->requestData['color']) ? json_encode($this->requestData['color']) : NULL;
            $post->friend_id = isset($this->requestData['friend']) ? $this->requestData['friend'] : NULL;
            $groupData = NULL;
            if (isset($this->requestData['group']) && $this->requestData['group']) {
                $groupData = \OlaHub\UserPortal\Models\groups::where('id', $this->requestData["group"])->first();
                $post->group_id = $this->requestData['group'];
                if ($groupData->posts_approve && $groupData->creator != app('session')->get('tempID')) {
                    $post->is_approve = 0;
                } else {
                    $post->is_approve = 1;
                }
            } else {
                $post->group_id = NULL;
                $post->is_approve = 1;
            }

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
                $post->post_images = !count($postImage) ? NULL : implode(",", $postImage);
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
                $post->post_videos = !count($postVideo) ? NULL : implode(",", $postVideo);
            }
            if (isset($this->requestData['group']) && $this->requestData['group']) {
                $group = $groupData;
                $owner = \OlaHub\UserPortal\Models\UserModel::where('id', $group->creator)->first();
                if ($group->posts_approve && $group->creator != app('session')->get('tempID')) {
                    $notification = new \OlaHub\UserPortal\Models\Notifications();
                    $notification->type = 'group';
                    $notification->content = "notifi_postGroup";
                    $notification->user_id = $group->creator;
                    $notification->friend_id = app('session')->get('tempID');
                    $notification->group_id = $group->id;
                    $notification->save();

                    $userData = app('session')->get('tempData');
                    \OlaHub\UserPortal\Models\Notifications::sendFCM(
                        $group->creator,
                        "add_post",
                        array(
                            "type" => "add_post",
                            "groupId" => $group->id,
                            "postId" => $post->post_id,
                            "user_data" => $userData,
                        ),
                        $owner->lang,
                        $group->name,
                        "$userData->first_name $userData->last_name"
                    );
                } else {
                    foreach ($group->members as $member) {
                        if ($member != app('session')->get('tempID')) {
                            $notification = new \OlaHub\UserPortal\Models\Notifications();
                            $notification->type = 'group';
                            $notification->content = "notifi_postGroup";
                            $notification->user_id = $member->user_id;
                            $notification->friend_id = app('session')->get('tempID');
                            $notification->group_id = $this->requestData['group'];
                            $notification->save();
                        }
                    }
                }
            }
            $post->save();
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($post, '\OlaHub\UserPortal\ResponseHandlers\PostsResponseHandler');
            $return['status'] = TRUE;
            $return['code'] = 200;
        }
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function addNewComment()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "addNewComment"]);

        $return = ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []];
        if (count($this->requestData) > 0 && TRUE /* \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(Post::$columnsMaping, $this->requestData) */) {
            $postID = $this->requestData['post_id'];
            $post = Post::where('post_id', $postID)->first();
            if ($post) {
                $comment = new PostComments();
                $comment->post_id = $postID;
                $comment->user_id = app('session')->get('tempID');
                $comment->comment = $this->requestData['content']['comment'];
                $comment->save();

                $author = app('session')->get('tempData');
                $authorName = "$author->first_name $author->last_name";
                $commentData = [
                    'comment_id' => $comment->id,
                    'user_id' => app('session')->get('tempID'),
                    'comment' => $comment->comment,
                    'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($comment->created_at),
                    'user_info' => [
                        'user_id' => $author->id,
                        'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($author->profile_picture),
                        'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($author, 'profile_url', $authorName, '.'),
                        'username' => $authorName,
                    ]
                ];
                $return['data'] = $commentData;
                $return['status'] = TRUE;
                $return['code'] = 200;

                if ($post->user_id != app('session')->get('tempID')) {
                    $notification = new \OlaHub\UserPortal\Models\Notifications();
                    $notification->type = 'post';
                    $notification->content = "notifi_comment";
                    $notification->user_id = $post->user_id;
                    $notification->friend_id = app('session')->get('tempID');
                    $notification->post_id = $postID;
                    $notification->save();

                    $userData = app('session')->get('tempData');
                    $owner = \OlaHub\UserPortal\Models\UserModel::where('id', $post->user_id)->first();
                    \OlaHub\UserPortal\Models\Notifications::sendFCM(
                        $post->user_id,
                        "post_comment",
                        array(
                            "type" => "post_comment",
                            "commentId" => $comment->id,
                            "postId" => $postID,
                            "subject" => $post->content,
                            "username" => "$userData->first_name $userData->last_name",
                        ),
                        $owner->lang,
                        "$userData->first_name $userData->last_name"
                    );
                }
            }
        }
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function getPostComments()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "getPostComments"]);

        if (isset($this->requestData['postId']) && $this->requestData['postId']) {
            $post = Post::where('post_id', $this->requestData['postId'])->first();
            if ($post) {
                if (isset($post->comments)) {
                    $return = [];
                    foreach ($post->comments as $comment) {
                        $userData = $comment->author;
                        $repliesData = [];
                        if (isset($comment->replies)) {
                            foreach ($comment->replies as $reply) {
                                $userReplyData = $reply->author;
                                $repliesData[] = [
                                    'reply_id' => $reply->id,
                                    'user_id' => $reply->user_id,
                                    'reply' => $reply->reply,
                                    'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($reply->created_at),
                                    'user_info' => [
                                        'user_id' => $userReplyData->id,
                                        'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userReplyData->profile_picture),
                                        'profile_url' => $userReplyData->profile_url,
                                        'username' => $userReplyData->first_name . " " . $userReplyData->last_name,
                                    ]
                                ];
                            }
                        }
                        $return["data"][] = [
                            'comment_id' => $comment->id,
                            'user_id' => $comment->user_id,
                            'comment' => $comment->comment,
                            'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($comment->created_at),
                            'user_info' => [
                                'user_id' => $userData->id,
                                'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->profile_picture),
                                'profile_url' => $userData->profile_url,
                                'username' => $userData->first_name . " " . $userData->last_name,
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

    public function addNewReply()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "addNewReply"]);

        $return = ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []];
        if (count($this->requestData) > 0 && TRUE /* \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(Post::$columnsMaping, $this->requestData) */) {
            $commentId = $this->requestData['comment_id'];
            $comment = PostComments::find($commentId);
            if ($comment) {
                $reply = new PostReplies();
                $reply->comment_id = $commentId;
                $reply->user_id = app('session')->get('tempID');
                $reply->reply = $this->requestData['content']['reply'];
                $reply->save();

                $author = app('session')->get('tempData');
                $authorName = "$author->first_name $author->last_name";

                $replyData = [
                    'reply_id' => $reply->id,
                    'user_id' => app('session')->get('tempID'),
                    'reply' => $reply->reply,
                    'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($reply->created_at),
                    'user_info' => [
                        'user_id' => $author->id,
                        'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($author->profile_picture),
                        'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($author, 'profile_url', $authorName, '.'),
                        'username' => $authorName,
                    ]
                ];
                if ($comment->user_id != app('session')->get('tempID')) {
                    $notification = new \OlaHub\UserPortal\Models\Notifications();
                    $notification->type = 'post';
                    $notification->content = "notifi_reply";
                    $notification->user_id = $comment->user_id;
                    $notification->friend_id = app('session')->get('tempID');
                    $notification->post_id = $this->requestData['post_id'];
                    $notification->save();
                    
                    $userData = app('session')->get('tempData');
                    $owner = \OlaHub\UserPortal\Models\UserModel::where('id', $comment->user_id)->first();
                    \OlaHub\UserPortal\Models\Notifications::sendFCM(
                        $comment->user_id,
                        "post_reply",
                        array(
                            "type" => "post_reply",
                            "commentId" => $comment->id,
                            "post_id" => $comment->post_id,
                            "replyId" => $reply->id,
                            "username" => "$userData->first_name $userData->last_name",
                        ),
                        $owner->lang,
                        "$userData->first_name $userData->last_name"
                    );
                }
                $return['data'] = $replyData;
                $return['status'] = TRUE;
                $return['code'] = 200;
            }
        }
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function deletePost()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "addNewReply"]);

        if (empty($this->requestData['postId'])) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        $post = Post::where('post_id', $this->requestData['postId'])->first();
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

    public function updatePost()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "addNewReply"]);

        if (empty($this->requestData['postId'])) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        if (isset($this->requestData['content']) && !$this->requestData['content']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => ['content' => ['validation.required']]]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => ['content' => ['validation.required']]], 200);
        }
        $post = Post::where('post_id', $this->requestData['postId'])->first();
        if ($post) {
            if ($post->user_id != app('session')->get('tempID')) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'Not allow to edit this post', 'code' => 400]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'Not allow to edit this post', 'code' => 400], 200);
            }

            $post->content = isset($this->requestData['content']) ? $this->requestData['content'] : NULL;
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
