<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\groups;
use OlaHub\UserPortal\Models\Post;
use OlaHub\UserPortal\Models\PostComments;
use OlaHub\UserPortal\Models\PostReplies;
use OlaHub\UserPortal\Models\PostShares;
use OlaHub\UserPortal\Models\PostReport;
use OlaHub\UserPortal\Models\VotePostUser;
use OlaHub\UserPortal\Models\PostVote;
use OlaHub\UserPortal\Models\PostLikes;

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
        $userID = $type == 'friend' ? $this->requestData['userId'] : app('session')->get('tempID');
        $user = \OlaHub\UserPortal\Models\UserModel::find($userID);
        $userInfo = [
            'user_id' => $user->id,
            'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($user->profile_picture),
            'profile_url' => $user->profile_url,
            'username' => "$user->first_name $user->last_name",
        ];
        if ($type == 'group') {
            $group = groups::where('id', $this->requestData['groupId'])->orWhere('slug', $this->requestData["groupId"])->first();
            $postsTemp = Post::where('group_id', $group->id)->where('is_approve', 1)->orderBy('created_at', 'desc')->paginate(15);

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

                $sharedItems = \OlaHub\UserPortal\Models\SharedItems::withoutGlobalScope('currentUser')
                    ->where(function ($q) use ($group) {
                        $q->where(function ($query) use ($group) {
                            $query->where('group_id', $group->id);
                        });
                    })->orderBy('created_at', 'desc')->paginate(20);
                if ($sharedItems->count()) {
                    foreach ($sharedItems as $litem) {
                        $usInfo = \OlaHub\UserPortal\Models\UserModel::find($litem->user_id);
                        if ($litem->item_type == 'store') {
                            $item = \OlaHub\UserPortal\Models\CatalogItem::where('id', $litem->item_id)->first();
                            $all[] = $this->handlePostShared($item, 'item_shared_store', $usInfo);
                        } else {
                            $item = \OlaHub\UserPortal\Models\DesignerItems::where('id', $litem->item_id)->first();
                            $all[] = $this->handlePostShared($item, 'item_shared_designer', $usInfo);
                        }
                    }
                }
                $sharedPosts = \OlaHub\UserPortal\Models\PostShares::withoutGlobalScope('currentUser')
                    ->where(function ($q) use ($group) {
                        $q->where(function ($query) use ($group) {
                            $query->where('group_id', $group->id);
                        });
                    })->orderBy('created_at', 'desc')->paginate(20);


                if ($sharedPosts->count()) {
                    foreach ($sharedPosts as $litem) {
                        $item = \OlaHub\UserPortal\Models\Post::where('post_id', $litem->post_id)->first();
                        $item = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($item, '\OlaHub\UserPortal\ResponseHandlers\PostsResponseHandler');
                        $item = $item['data'];
                        $item['type'] = 'post_shared';
                        $item['sharedUser_info'] = [
                            'user_id' => $litem->author->id,
                            'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($litem->author->profile_picture),
                            'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($litem->author, 'profile_url', $litem->user_name, '.'),
                            'username' => $litem->user_name,
                        ];
                        $item['shared_time'] = isset($litem->created_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($litem->created_at) : NULL;
                        $all[] = $item;
                    }
                }

                shuffle($all);
                $return = ['status' => true, 'data' => $all, 'meta' => isset($posts["meta"]) ? $posts["meta"] : [], 'code' => 200];
            }
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
            return response($return, 200);
        } else {
            $isFriend = true;
            $myGroups = \OlaHub\UserPortal\Models\GroupMembers::getGroupsArr(app('session')->get('tempID'));
            if ($userID != app('session')->get('tempID')) {
                $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList(app('session')->get('tempID'));
                if (!in_array($userID, $friends)) {
                    $isFriend = false;
                }
            }

            if ($isFriend) {
                $posts = Post::where(function ($q) use ($userID, $myGroups) {
                    $q->where(function ($userPost) use ($userID) {
                        $userPost->where('user_id', $userID);
                        $userPost->where('friend_id', NULL);
                    });
                    $q->orWhere(function ($userPost) use ($userID) {
                        $userPost->where('user_id', app('session')->get('tempID'));
                        $userPost->where('friend_id', $userID);
                    });
                    $q->orWhere(function ($userPost) use ($userID, $myGroups) {
                        $userPost->where('user_id', $userID);
                        $userPost->whereIn('group_id', $myGroups);
                    });
                });
                if ($userID != app('session')->get('tempID')) {
                    $posts->where('privacy', '!=', 3);
                }
            } else {
                $posts = Post::where(function ($q) use ($userID, $myGroups) {
                    $q->where(function ($userPost) use ($userID) {
                        $userPost->where('user_id', $userID);
                        $userPost->where('friend_id', NULL);
                        $userPost->where('group_id', NULL);
                    });
                    $q->orWhere(function ($userPost) use ($userID, $myGroups) {
                        $userPost->where('user_id', $userID);
                        $userPost->whereIn('group_id', $myGroups);
                    });
                });
                $posts->where('privacy', '=', 1);
            }
            $posts = $posts->orderBy('created_at', 'desc')->paginate(20);
        }
        if ($posts->count() > 0) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($posts, '\OlaHub\UserPortal\ResponseHandlers\PostsResponseHandler');
            // $posts = $posts['data'];
            if ($type != 'group') {
                $sharedItems = \OlaHub\UserPortal\Models\SharedItems::withoutGlobalScope('currentUser')
                    ->where(function ($q) use ($userID) {
                        $q->where(function ($query) use ($userID) {
                            $query->where('user_id', $userID);
                            $query->where('group_id', NULL);
                        });
                    })->orderBy('created_at', 'desc')->paginate(20);
                if ($sharedItems->count()) {
                    foreach ($sharedItems as $litem) {
                        $usInfo = \OlaHub\UserPortal\Models\UserModel::find($litem->user_id);
                        if ($litem->item_type == 'store') {
                            $item = \OlaHub\UserPortal\Models\CatalogItem::where('id', $litem->item_id)->first();
                            $return['data'][] = $this->handlePostShared($item, 'item_shared_store', $usInfo);
                        } else {
                            $item = \OlaHub\UserPortal\Models\DesignerItems::where('id', $litem->item_id)->first();
                            $return['data'][] = $this->handlePostShared($item, 'item_shared_designer', $usInfo);
                        }
                    }
                }

                $sharedPosts = \OlaHub\UserPortal\Models\PostShares::withoutGlobalScope('currentUser')
                    ->where(function ($q) use ($userID) {
                        $q->where(function ($query) use ($userID) {
                            $query->where('user_id', $userID);
                            $query->where('group_id', NULL);
                        });
                    })->orderBy('created_at', 'desc')->paginate(20);
                if ($sharedPosts->count()) {
                    foreach ($sharedPosts as $litem) {

                        $item = \OlaHub\UserPortal\Models\Post::where('post_id', $litem->post_id)->first();
                        $item = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($item, '\OlaHub\UserPortal\ResponseHandlers\PostsResponseHandler');
                        $item = $item['data'];
                        $item['type'] = 'post_shared';
                        $item['sharedUser_info'] = [
                            'user_id' => $litem->author->id,
                            'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($litem->author->profile_picture),
                            'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($litem->author, 'profile_url', $litem->user_name, '.'),
                            'username' => $litem->user_name,
                        ];

                        $item['shared_time'] = isset($litem->created_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($litem->created_at) : NULL;
                        $return['data'][] = $item;
                    }
                }
            }


            shuffle($return['data']);


            // $return['data'] = $posts;
            $return['status'] = TRUE;
            $return['code'] = 200;
            // unset($return['message']);
        }
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }


    private function handlePostShared($data, $type, $userInfo)
    {
        $return = [
            'user_info' => $userInfo,
            'time' => isset($data->created_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($data->created_at) : NULL,
        ];
        $images = $data->images;
        $return['type'] = 'item_shared';
        $return['item_id'] = $data->id;
        $return['item_slug'] = $data->item_slug;
        $return['item_title'] = $data->name;
        $return['item_desc'] = isset($data->description) ? strip_tags($data->description) : NULL;
        $return['avatar_url'] = count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : NULL;
        switch ($type) {
            case 'item_shared_store':
                $brand = $data->brand;
                $return['target'] = 'store';
                $return['merchant_info'] = [
                    'type' => 'brand',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brand->image_ref),
                    'merchant_slug' => isset($brand->store_slug) ? $brand->store_slug : NULL,
                    'merchant_title' => isset($brand->name) ? $brand->name : NULL,
                ];
                break;
            case 'item_shared_designer':
                $designer = $data->designer;
                $return['target'] = 'designer';
                $return['merchant_info'] = [
                    'type' => 'designer',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($designer->logo_ref),
                    'merchant_slug' => isset($designer->designer_slug) ? $designer->designer_slug : NULL,
                    'merchant_title' => isset($designer->brand_name) ? $designer->brand_name : NULL,
                ];
                break;
        }
        return $return;
    }

    public function getOnePost()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "getOnePost"]);

        $return = ['status' => false, 'msg' => 'NoData', 'code' => 204];
        if (isset($this->requestData['postId']) && $this->requestData['postId']) {
            if (app('session')->get('tempID')) {
                $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList(app('session')->get('tempID'));
                $myGroups = \OlaHub\UserPortal\Models\GroupMembers::getGroupsArr(app('session')->get('tempID'));

                $post = Post::where('post_id', $this->requestData['postId'])
                    ->where(function ($q) use ($friends, $myGroups) {
                        $q->where(function ($query) use ($friends, $myGroups) {
                            $query->whereIn('user_id', $friends);
                            $query->Where('privacy', 2);
                            $query->where(function ($q1) use ($myGroups) {
                                $q1->whereIn('group_id', $myGroups);
                                $q1->orWhere('group_id', NULL);
                            });
                        });
                        $q->orwhere(function ($query2) use ($myGroups) {
                            $query2->Where('privacy', 2);
                            $query2->whereIn('group_id', $myGroups);
                        });
                        $q->orWhere('privacy', 1);
                        $q->orWhere('user_id', app('session')->get('tempID'));
                    })
                    ->first();
            } else {
                $post = Post::where('post_id', $this->requestData['postId'])->where('privacy', 1)->first();
            }

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

    public function usersLike()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "usersLike"]);

        $return = ['status' => false, 'msg' => 'NoData', 'code' => 204];
        if (isset($this->requestData['postId']) && $this->requestData['postId']) {
            $post = Post::where('post_id', $this->requestData['postId'])->first();

            if ($post) {
                $likes = PostLikes::where('post_id', $this->requestData['postId'])->orderBy('created_at', 'desc')->paginate(20);

                // $likes = $post->likes;
                $likerData = [];
                foreach ($likes as $like) {
                    $userData = $like->author;
                    $name = isset($userData->first_name) ? $userData->first_name . ' ' . $userData->last_name : "";

                    $likerData[] = [
                        'likerPhoto' => isset($userData->profile_picture) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->profile_picture) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                        'likerProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL,
                        'likerName' => isset($name) ? $name : NULL,
                        'likerid' => isset($userData->id) ? $userData->id  : NULL
                    ];
                }
               
                $return['data'] = $likerData;
                $return['lastPage'] = $likes->lastPage();
                $return['status'] = TRUE;
                $return['code'] = 200;
            }
        }
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }
    public function getTophashTags()
    {
        $allHash = [];
        if (app('session')->get('tempID')) {
            $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList(app('session')->get('tempID'));
            $myGroups = \OlaHub\UserPortal\Models\GroupMembers::getGroupsArr(app('session')->get('tempID'));
            $topHashTags = Post::Where('content', 'like', '%#%')
                ->where(function ($q) use ($friends, $myGroups) {
                    $q->where(function ($query) use ($friends, $myGroups) {
                        $query->whereIn('user_id', $friends);
                        $query->Where('privacy', 2);
                        $query->where(function ($q1) use ($myGroups) {
                            $q1->whereIn('group_id', $myGroups);
                            $q1->orWhere('group_id', NULL);
                        });
                    });
                    $q->orwhere(function ($query2) use ($myGroups) {
                        $query2->Where('privacy', 2);
                        $query2->whereIn('group_id', $myGroups);
                    });
                    $q->orWhere('privacy', 1);
                    $q->orWhere('user_id', app('session')->get('tempID'));
                })
                ->get();
        } else {
            $topHashTags = Post::Where('content', 'like', '%#%')
                ->where('privacy', 1)->get();
        }
        foreach ($topHashTags as $hash) {
            $onePostHash = [];
            $content = str_replace('<br>', ' ', $hash->content);
            $bits = explode(' ', $content);
            foreach ($bits as $bit) {
                if (strlen($bit) > 0 && $bit[0] === '#' && !(in_array($bit, $onePostHash))) {
                    $allHash[] = $bit;
                    $onePostHash[] = $bit;
                };
            }
        }
        $x = array_count_values($allHash);
        arsort($x);
        $i = 0;
        $topFive = [];

        foreach ($x as $oneHash => $key) {
            if ($i == 5) {
                return (($topFive));
            }
            $topFive[$i] = ['hash' => $oneHash, 'count' => $key];
            $i++;
        }

        return (($topFive));
    }

    public function addNewPost()
    {
        if (isset($this->requestData['mentions'])) {
            $allMentions = serialize($this->requestData['mentions']);
        }

        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');


        $return = ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []];
        if (count($this->requestData) > 0 && TRUE /* \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(Post::$columnsMaping, $this->requestData) */) {

            $post = new Post;
            $post->user_id = app('session')->get('tempID');
            $post->post_id = uniqid(app('session')->get('tempID'));
            $post->mentions = isset($allMentions) ? $allMentions : NULL;
            $post->content = isset($this->requestData['content']) ? $this->requestData['content'] : NULL;
            $post->color = isset($this->requestData['color']) ? json_encode($this->requestData['color']) : NULL;
            $post->privacy = isset($this->requestData['privacy']) ? json_encode($this->requestData['privacy']) : 2;
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
                switch ($groupData->privacy) {
                    case "1":
                        $post->privacy = 3;
                        break;
                    case "2":
                        $post->privacy = 2;
                        break;
                    case "3":
                        $post->privacy = 1;
                        break;
                    default:
                        $post->privacy = 1;
                        break;
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
                        if ($member->user_id != app('session')->get('tempID')) {
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

            $post->post_id = uniqid(app('session')->get('tempID'));
            if (isset($this->requestData['friend'])) {
                $notification = new \OlaHub\UserPortal\Models\Notifications();
                $notification->type = 'post';
                $notification->content = "notifi_Friend_Post";
                $notification->friend_id = $post->user_id;
                $notification->user_id = $this->requestData['friend'];
                $notification->post_id = $post->post_id;
                $notification->save();

                $userData = app('session')->get('tempData');
                $owner = \OlaHub\UserPortal\Models\UserModel::where('id', $post->user_id)->first();
                \OlaHub\UserPortal\Models\Notifications::sendFCM(
                    $post->user_id,
                    "add_post_friend",
                    array(
                        "type" => "add_post_friend",
                        "postId" => $post->post_id,
                        "subject" => $post->content,
                        "username" => "$userData->first_name $userData->last_name",
                    ),
                    $owner->lang,
                    "$userData->first_name $userData->last_name"
                );
            }
            if (!isset($this->requestData['friend']) && !isset($this->requestData['group'])) {
                $userData = app('session')->get('tempData');
                $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList(app('session')->get('tempID'));
                foreach ($friends as $friend) {
                    $owner = \OlaHub\UserPortal\Models\UserModel::where('id', $friend)->first();

                    $notification = new \OlaHub\UserPortal\Models\Notifications();

                    if (isset($this->requestData['isVote']) && ($this->requestData['isVote'] == "true")) {

                        $notiContent = 'notifi_user_vote_Post';
                    } else {

                        $notiContent = 'notifi_user_new_Post';
                    }
                    $notification->type = 'post';
                    $notification->content = $notiContent;
                    $notification->user_id = $friend;
                    $notification->friend_id = app('session')->get('tempID');
                    $notification->post_id = $post->post_id;
                    $notification->save();
                    \OlaHub\UserPortal\Models\Notifications::sendFCM(
                        $friend,
                        $notiContent,
                        array(
                            "type" => $notiContent,
                            "postId" => $post->post_id,
                            "subject" => $post->content,
                            "username" => "$userData->first_name $userData->last_name",
                        ),
                        @$owner->lang || "en",
                        "$userData->first_name $userData->last_name"
                    );
                }
            }



            $post->save();


            if (isset($this->requestData['mentions'])) {
                $Mentions = $this->requestData['mentions'];
                foreach ($Mentions as $mention) {
                    $MentionedUserId = \OlaHub\UserPortal\Models\UserModel::where('profile_url', $mention['user'])->first();
                    $userId =  $MentionedUserId->id;
                    $notification = new \OlaHub\UserPortal\Models\Notifications();

                    $notiContent = 'notifi_mention_post';

                    $notification->type = 'post';
                    $notification->content = $notiContent;
                    $notification->user_id = $userId;
                    $notification->friend_id = app('session')->get('tempID');
                    $notification->post_id = $post->post_id;
                    $notification->save();
                    \OlaHub\UserPortal\Models\Notifications::sendFCM(
                        $userId,
                        $notiContent,
                        array(
                            "type" => $notiContent,
                            "postId" => $post->post_id,
                            "subject" => $post->content,
                            "username" => "$userData->first_name $userData->last_name",
                        ),
                        $owner->lang,
                        "$userData->first_name $userData->last_name"
                    );
                }
            }
            if (isset($this->requestData['isVote']) && $this->requestData['isVote'] == true) {
                $postVote = new PostVote;
                $dataRows = [];
                if (!empty($this->requestData['optionsTextData'])) {
                    foreach ($this->requestData['optionsTextData'] as $value) {
                        $dataRows[] = array(
                            'post_id' => $post->post_id,
                            'end_date' => $this->requestData['voteEndDate'],
                            'option' => $value,
                            'type' => 'text',
                            'start_date' => \Carbon\Carbon::now()
                        );
                    }
                }
                if (!empty($this->requestData['voteItems'])) {
                    foreach ($this->requestData['voteItems'] as $value) {
                        $dataRows[] = array(
                            'post_id' => $post->post_id,
                            'end_date' => $this->requestData['voteEndDate'],
                            'option' => $value['value'],
                            'type' => $value['type']
                        );
                    }
                }
                $postVote::insert($dataRows);
            }
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($post, '\OlaHub\UserPortal\ResponseHandlers\PostsResponseHandler');
            $return['status'] = TRUE;
            $return['code'] = 200;
        }
        // $log->setLogSessionData(['response' => $return]);
        // $log->saveLogSessionData();
        $log->saveLog($userData->id, $this->requestData, 'Add Post');

        return response($return, 200);
    }

    public function likePost()
    {
        $status = $this->requestData['status'];
        $postId = $this->requestData['postId'];
        $post = Post::where('post_id', $postId)->first();
        $likes = $post->likes;
        $comments = $post->comments;
        $followers = [];
        foreach ($likes as $userID) {
            $x = "ID" . $userID->user_id;
            $followers[$x] = [

                'user_id' => $userID->user_id
            ];
        }
        foreach ($comments as $userID) {
            $x = "ID" . $userID->user_id;
            $followers[$x] = [

                'user_id' => $userID->user_id
            ];
        }

        if ($status) {
            $like = (new \OlaHub\UserPortal\Models\PostLikes);
            $like->post_id = $postId;
            $like->user_id = app('session')->get('tempID');
            $like->save();
            foreach ($followers as $userId) {

                if ($post->user_id != app('session')->get('tempID') && ($post->user_id !=  $userId['user_id'])) {
                    $notification = new \OlaHub\UserPortal\Models\Notifications();
                    $notification->type = 'post';
                    $notification->content = "notifi_post_like_for_follower";
                    $notification->user_id = $userId['user_id'];
                    $notification->friend_id = app('session')->get('tempID');
                    $notification->post_id = $postId;
                    $notification->save();

                    $userData = app('session')->get('tempData');
                    $posterName = \OlaHub\UserPortal\Models\UserModel::where('id', $post->user_id)->first();
                    $owner = \OlaHub\UserPortal\Models\UserModel::where('id', $userId['user_id'])->first();
                    \OlaHub\UserPortal\Models\Notifications::sendFCM(
                        $userId['user_id'],
                        "notifi_post_like_for_follower",
                        array(
                            "type" => "notifi_post_like_for_follower",
                            "postId" => $postId,
                            "subject" => $post->content,
                            "username" => "$userData->first_name $userData->last_name",

                        ),
                        $owner->lang,
                        "$userData->first_name $userData->last_name",
                        "$posterName->first_name $posterName->last_name"
                    );
                }
            }

            if ($post->user_id != app('session')->get('tempID')) {
                $notification = new \OlaHub\UserPortal\Models\Notifications();
                $notification->type = 'post';
                $notification->content = "notifi_post_like";
                $notification->user_id = $post->user_id;
                $notification->friend_id = app('session')->get('tempID');
                $notification->post_id = $postId;
                $notification->save();

                $userData = app('session')->get('tempData');
                $owner = \OlaHub\UserPortal\Models\UserModel::where('id', $post->user_id)->first();
                \OlaHub\UserPortal\Models\Notifications::sendFCM(
                    $post->user_id,
                    "post_like",
                    array(
                        "type" => "post_like",
                        "postId" => $postId,
                        "subject" => $post->content,
                        "username" => "$userData->first_name $userData->last_name",
                    ),
                    $owner->lang,
                    "$userData->first_name $userData->last_name"
                );
            }
        } else {
            \OlaHub\UserPortal\Models\PostLikes::where('post_id', $postId)->where('user_id', app('session')->get('tempID'))->delete();
            \OlaHub\UserPortal\Models\Notifications::where('type', 'notifi_post_like')->where('user_id', $post->user_id)->where('friend_id', app('session')->get('tempID'))->delete();
        }

        $return['status'] = TRUE;
        $return['code'] = 200;
        return response($return, 200);
    }

    public function sharePost()
    {
        $status = $this->requestData['status'];
        $postId = $this->requestData['postId'];
        if ($status) {
            $share = (new \OlaHub\UserPortal\Models\PostShares);
            $share->post_id = $postId;
            $share->user_id = app('session')->get('tempID');
            $share->save();
        } else {
            \OlaHub\UserPortal\Models\PostShares::where('post_id', $postId)->where('user_id', app('session')->get('tempID'))->delete();
        }
        $return['status'] = TRUE;
        $return['code'] = 200;
        return response($return, 200);
    }

    public function newSharePost()
    {

        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $userData = app('session')->get('tempData');
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "sharePost"]);

        if (isset($this->requestData['postId']) && !$this->requestData['postId']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        $groupId = isset($this->requestData['groupId']) && $this->requestData['groupId'] != 0  ? $this->requestData['groupId'] : NULL;
        $shared = PostShares::where('post_id', $this->requestData['postId'])
            ->where('group_id', $groupId)
            ->where('user_id', app('session')->get('tempID'))->first();
        $update = false;
        $item = [];
        if (!$shared) {
            $share = new PostShares;
            $share->post_id = $this->requestData['postId'];
            $share->group_id = $groupId;
            $share->user_id = app('session')->get('tempID');
            $share->save();
            $update = true;

            $litem = PostShares::where('id', $share->id)->first();

            $item = \OlaHub\UserPortal\Models\Post::where('post_id', $litem->post_id)->first();
            $item = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($item, '\OlaHub\UserPortal\ResponseHandlers\PostsResponseHandler');
            $item = $item['data'];
            $item['type'] = 'post_shared';
            $item['sharedUser_info'] = [
                'user_id' => $litem->author->id,
                'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($litem->author->profile_picture),
                'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($litem->author, 'profile_url', $litem->user_name, '.'),
                'username' => $litem->user_name,
            ];

            $item['shared_time'] = isset($litem->created_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($litem->created_at) : NULL;
            $return['data'][] = $item;
        }

        $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'newSharedPostUser', 'code' => 200, 'update' => $update, 'data' => $item]]);
        $log->saveLogSessionData();

        return response(['status' => TRUE, 'code' => 200, 'update' => $update, 'data' => $item], 200);
    }

    public function addNewComment()
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');

        $return = ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []];
        if (count($this->requestData) > 0 && TRUE /* \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(Post::$columnsMaping, $this->requestData) */) {
            $postID = $this->requestData['post_id'];
            $post = Post::where('post_id', $postID)->first();

            $likes = $post->likes;
            $comments = $post->comments;
            $followers = [];
            foreach ($likes as $userID) {
                $x = "ID" . $userID->user_id;
                $followers[$x] = [

                    'user_id' => $userID->user_id
                ];
            }
            foreach ($comments as $userID) {
                $x = "ID" . $userID->user_id;
                $followers[$x] = [

                    'user_id' => $userID->user_id
                ];
            }

            if ($post) {
                $comment = new PostComments();
                $comment->post_id = $postID;
                $comment->user_id = app('session')->get('tempID');
                $comment->comment = $this->requestData['content']['comment'];
                $comment->save();


                foreach ($followers as $userId) {

                    if ($post->user_id != app('session')->get('tempID') && ($post->user_id !=  $userId['user_id'])) {
                        $notification = new \OlaHub\UserPortal\Models\Notifications();
                        $notification->type = 'post';
                        $notification->content = "notifi_post_comment_for_follower";
                        $notification->user_id = $userId['user_id'];
                        $notification->friend_id = app('session')->get('tempID');
                        $notification->post_id = $postID;
                        $notification->save();

                        $userData = app('session')->get('tempData');
                        $posterName = \OlaHub\UserPortal\Models\UserModel::where('id', $post->user_id)->first();

                        $owner = \OlaHub\UserPortal\Models\UserModel::where('id', $userId['user_id'])->first();
                        \OlaHub\UserPortal\Models\Notifications::sendFCM(
                            $userId['user_id'],
                            "notifi_post_comment_for_follower",
                            array(
                                "type" => "notifi_post_comment_for_follower",
                                "postId" => $postID,
                                "subject" => $post->content,
                                "username" => "$userData->first_name $userData->last_name",

                            ),
                            $owner->lang,
                            "$userData->first_name $userData->last_name",
                            "$posterName->first_name $posterName->last_name"
                        );
                    }
                }


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
                $postID = $this->requestData['post_id'];

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
        $log->saveLog($userData->id, $this->requestData, 'Add Comment');

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
                    $return["data"] = [];
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
                        $canEdit = false;
                        $canDelete = false;
                        $canEditReply = false;
                        $canDeleteReply = false;
                        if ((app('session')->get('tempID')) == $comment->user_id) {
                            $canEdit = true;
                            $canDelete = true;
                        }
                        $post = Post::where('post_id', $this->requestData['postId'])->first();
                        if ($post) {
                            if ($post->user_id == app('session')->get('tempID')) {
                                $canDelete = true;
                            }
                        }
                        if ((app('session')->get('tempID')) == $comment->user_id) {
                            $canEditReply = true;
                            $canDeleteReply = true;
                        }
                        if ($post) {
                            if ($post->user_id == app('session')->get('tempID')) {
                                $canDeleteReply = true;
                            }
                        }

                        $return["data"][] = [
                            'comment_id' => $comment->id,
                            'user_id' => $comment->user_id,
                            'canUpdate' => $canEdit,
                            'canDelete' => $canDelete,
                            'canEditReply' => $canEditReply,
                            'canDeleteReply' => $canDeleteReply,
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
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
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
        $log->saveLog($userData->id, $this->requestData, 'Reply');

        return response($return, 200);
    }

    public function deletePost()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "deletePost"]);

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
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "updatePost"]);

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

    public function deleteComment()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "PostComments", 'function_name' => "deleteComment"]);

        if (empty($this->requestData['commentId'])) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }


        $Comment = PostComments::where('Id', $this->requestData['commentId'])->first();
        $post = Post::where('post_id', $Comment->post_id)->first();
        if ($Comment) {
            if ($Comment->user_id != app('session')->get('tempID') && $post->user_id != app('session')->get('tempID')) {
                if (isset($Comment->group_id) && $Comment->group_id > 0) {
                    $group = \OlaHub\UserPortal\Models\groups::where('creator', app('session')->get('tempID'))->find($Comment->group_id);
                    if (!$group) {
                        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'Not allow to delete this Comment', 'code' => 400]]);
                        $log->saveLogSessionData();
                        return response(['status' => false, 'msg' => 'Not allow to delete this Comment', 'code' => 400], 200);
                    }
                } else {
                    $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'Not allow to delete this Comment', 'code' => 400]]);
                    $log->saveLogSessionData();
                    return response(['status' => false, 'msg' => 'Not allow to delete this Comment', 'code' => 400], 200);
                }
            }
            $Comment->delete = 1;
            $Comment->save();
            $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'You delete Comment successfully', 'code' => 200]]);
            $log->saveLogSessionData();
            return response(['status' => true, 'msg' => 'You delete Comment successfully', 'code' => 200], 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function updateComment()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "PostComments", 'function_name' => "updateComment"]);
        if (empty($this->requestData['commentId'])) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        if (isset($this->requestData['content']) && !$this->requestData['content']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => ['content' => ['validation.required']]]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => ['content' => ['validation.required']]], 200);
        }
        $comment = PostComments::where('Id', $this->requestData['commentId'])->first();
        if ($comment) {
            if ($comment->user_id != app('session')->get('tempID')) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'Not allow to edit this comment', 'code' => 400]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'Not allow to edit this comment', 'code' => 400], 200);
            }

            $comment->comment = isset($this->requestData['content']) ? $this->requestData['content'] : NULL;
            $comment->save();
            // $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($comment, '\OlaHub\UserPortal\ResponseHandlers\PostsResponseHandler');
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
    public function hashPost()
    {

        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "getHashPost"]);
        $return = ['status' => false, 'msg' => 'NoData', 'code' => 204];
        if (isset($this->requestData['postHash']) && $this->requestData['postHash']) {

            $hashTag = substr_replace($this->requestData['postHash'], '#' . $this->requestData['postHash'], 0);
            $post = Post::where('content', 'LIKE', '%' . $hashTag . '%')->paginate(15);
            if (app('session')->get('tempID')) {
                $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList(app('session')->get('tempID'));
                $myGroups = \OlaHub\UserPortal\Models\GroupMembers::getGroupsArr(app('session')->get('tempID'));

                $post = Post::where('content', 'LIKE', '%' . $hashTag . '%')
                    ->where(function ($q) use ($friends, $myGroups) {
                        $q->where(function ($query) use ($friends, $myGroups) {
                            $query->whereIn('user_id', $friends);
                            $query->Where('privacy', 2);
                            $query->where(function ($q1) use ($myGroups) {
                                $q1->whereIn('group_id', $myGroups);
                                $q1->orWhere('group_id', NULL);
                            });
                        });
                        $q->orwhere(function ($query2) use ($myGroups) {
                            $query2->Where('privacy', 2);
                            $query2->whereIn('group_id', $myGroups);
                        });
                        $q->orWhere('privacy', 1);
                        $q->orWhere('user_id', app('session')->get('tempID'));
                    })
                    ->paginate(15);
            } else {
                $post = Post::where('content', 'LIKE', '%' . $hashTag . '%')->where('privacy', 1)->paginate(15);
            }

            if ($post) {
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($post, '\OlaHub\UserPortal\ResponseHandlers\PostsResponseHandler');
                $return['status'] = TRUE;
                $return['code'] = 200;
            }
        }
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function removeSharePost()
    {

        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $userData = app('session')->get('tempData');
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "removeSharePost"]);

        if (isset($this->requestData['postId']) && !$this->requestData['postId']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        $groupId = isset($this->requestData['groupId']) && $this->requestData['groupId'] != 0  ? $this->requestData['groupId'] : NULL;
        $shared = PostShares::where('post_id', $this->requestData['postId'])
            ->where('group_id', $groupId)
            ->where('user_id', app('session')->get('tempID'))->delete();

        $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'RemoveSharedPost', 'code' => 200]]);
        $log->saveLogSessionData();

        return response(['status' => TRUE, 'code' => 200], 200);
    }

    public function ReportPost()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "ReportPost"]);

        if (empty($this->requestData['postId'])) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        $post = Post::where('post_id', $this->requestData['postId'])->first();
        $user = app('session')->get('tempID');
        $postId = $this->requestData['postId'];
        if ($post) {
            $report = new PostReport();
            $report->post_id = $postId;
            $report->user_id = app('session')->get('tempID');
            $report->save();

            $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'You report post successfully', 'code' => 200]]);
            $log->saveLogSessionData();
            return response(['status' => true, 'msg' => 'You report post successfully', 'code' => 200], 200);
        }
    }

    public function votersOnPost()
    {

        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $userData = app('session')->get('tempData');
        $log->setLogSessionData(['module_name' => "VotePostUser", 'function_name' => "VotersOnPost"]);
        $postId = $this->requestData['postId'];
        $post = Post::where('post_id', $postId)->first();
        if (empty($this->requestData['optionId'])) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }

        $user_vote = new VotePostUser();
        $user_vote->user_id = app('session')->get('tempID');
        $user_vote->vote_id = $this->requestData['optionId'];
        $user_vote->save();
        if ($post->user_id != app('session')->get('tempID')) {

            $notification = new \OlaHub\UserPortal\Models\Notifications();
            $notification->type = 'post';
            $notification->content = "notifi_vote_on_post";
            $notification->user_id = $post->user_id;
            $notification->friend_id = app('session')->get('tempID');
            $notification->post_id = $postId;
            $notification->save();

            $userData = app('session')->get('tempData');
            $owner = \OlaHub\UserPortal\Models\UserModel::where('id', $post->user_id)->first();
            \OlaHub\UserPortal\Models\Notifications::sendFCM(
                $post->user_id,
                "notifi_vote_on_post",
                array(
                    "type" => "notifi_vote_on_post",
                    "postId" => $postId,
                    "subject" => $post->content,
                    "username" => "$userData->first_name $userData->last_name",
                ),
                $owner->lang,
                "$userData->first_name $userData->last_name"
            );
        }
        $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'You voteing post successfully', 'code' => 200]]);
        $log->saveLogSessionData();
        return response(['status' => true, 'msg' => 'You vote post successfully', 'code' => 200], 200);
    }
    public function updatePrivacyPost()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Posts", 'function_name' => "updatePrivacyPost"]);

        if (empty($this->requestData['postId'])) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        if (isset($this->requestData['privacy']) && !$this->requestData['privacy']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => ['privacy' => ['validation.required']]]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => ['privacy' => ['validation.required']]], 200);
        }
        $post = Post::where('post_id', $this->requestData['postId'])->first();
        if ($post) {
            if ($post->user_id != app('session')->get('tempID')) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'Not allow to edit this post', 'code' => 400]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'Not allow to edit this post', 'code' => 400], 200);
            }

            $post->privacy = isset($this->requestData['privacyID']) ? $this->requestData['privacyID'] : NULL;

            // var_dump($this->requestData['privacyID']);
            $post->save();

            // var_dump($post);return;
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
