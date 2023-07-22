<?php

namespace App\Services;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class PostService
{
    private $baseUrl;
    private $token;

    public function __construct()
    {
        $this->baseUrl = "https://www.ensembledata.com/apis/instagram/";
        $this->token = "1pRVMKHb4jAgb4hb";
    }

    private function  getPostsFromCelebrities($username)
    {
        $message = "";
        $error = "";
        $status = false;
        $data = null;

        try {

            $endpiont = $this->baseUrl . "user/posts-from-username";

            $httpRequest = Http::acceptJson()->get($endpiont, [
                "username" => $username,
                "depth" => 1,
                "oldest_timestamp" => time(),
                "chunk_size" => 5,
                "start_cursor" => "",
                "token" => $this->token
            ]);

            if ($httpRequest->status() === Response::HTTP_OK) {
                $requestBody = json_decode($httpRequest->body());
                $data = $requestBody->data->posts;
                $message = "Posts fetched";
                $status = true;
            } else {
                $message = "Posts could not be fetched";
            }
        } catch (Throwable $th) {
            $error = $th->getMessage();
            $message = "There was an error please try again";
        }

        return [
            "error" => $error,
            "message" => $message,
            "data" => $data,
            "status" => $status
        ];
    }

    private function  getPostsFromManyCelebs(array $celebs)
    {
        $message = "";
        $error = "";
        $status = false;
        $data = null;

        try {
            $endpiont = $this->baseUrl . "user/posts-from-username";
            $celebOneParams = $this->celebsParams($celebs);
            $responses = Http::pool(fn (Pool $pool) => [
                $pool->get($endpiont, $celebOneParams['one']),
                $pool->get($endpiont, $celebOneParams['two']),
                $pool->get($endpiont, $celebOneParams['three']),
                $pool->get($endpiont, $celebOneParams['four']),
                $pool->get($endpiont, $celebOneParams['five']),
            ]);

            if (
                $responses[0]->ok() &&
                $responses[1]->ok() &&
                $responses[2]->ok() &&
                $responses[3]->ok() &&
                $responses[4]->ok()
            ) {
                $responseBodyOne = json_decode($responses[0]->body());
                $postDataOne = $responseBodyOne->data->posts;
                $responseBodyTwo = json_decode($responses[1]->body());
                $postDataTwo = $responseBodyTwo->data->posts;
                $responseBodyThree = json_decode($responses[2]->body());
                $postDataThree = $responseBodyThree->data->posts;
                $responseBodyFour = json_decode($responses[3]->body());
                $postDataFour = $responseBodyFour->data->posts;
                $responseBodyFive = json_decode($responses[4]->body());
                $postDataFive = $responseBodyFive->data->posts;

                $message = "Posts fetched";
                $status = true;
                $data = [
                    $postDataOne,
                    $postDataTwo,
                    $postDataThree,
                    $postDataFour,
                    $postDataFive
                ];
            } {
                $message = "Posts could not be fetched";
            }
        } catch (Throwable $th) {
            $error = $th->getMessage();
            $message = "There was an error please try again";
        }

        return [
            "error" => $error,
            "message" => $message,
            "data" => $data,
            "status" => $status
        ];
    }

    private function formatPostPerCelebrity($postsData): array
    {
        return $postsData;
        $postBody = $postsData->caption->text;
        $postedAt = Carbon::parse($postsData->caption->created_at);
        $postowner = $postsData->user->full_name;
        $commentCount = $postsData->comment_count;
        $likeCount = $postsData->like_count;
        $media = array_column($postsData->image_versions2->candidates, "url");
        return [
            "postBody" => $postBody,
            "user" => $postowner,
            "commentCount" => $commentCount,
            "likeCount" => $likeCount,
            "media" => $media,
            "postedAt" => $postedAt,
            "title" => null
        ];
    }

    public function savePosts($username)
    {
        $message = "";
        $error = "";
        $status = false;
        $data = null;
        try {

            $posts = $this->getPostsFromCelebrities($username);
            return $posts;
            $formattedPostData = $this->formatSinglePost($posts);
            if ($formattedPostData['status']) {
                $status = true;
                $message = "Post fetched and also inserted into the database";
            }
            $data = $formattedPostData['data'];
        } catch (Throwable $th) {
            $message = "there was an error";
            $error = $th->getMessage();
        }
        return [
            "error" => $error,
            "message" => $message,
            "data" => $data,
            "status" => $status
        ];
        return $formattedPostData;
    }


    public function saveMultiplePosts(array $celebs)
    {
        $message = "";
        $error = "";
        $status = false;
        $data = null;
        try {

            $celebsPosts = $this->getPostsFromManyCelebs($celebs);

            foreach ($celebsPosts["data"] as $posts) {
                $data[] = $this->formatArrayOfPosts($posts);
            }

            if ($celebsPosts['status']) {
                $status = true;
                $message = "Post fetched and also inserted into the database";
            }
        } catch (Throwable $th) {
            $message = "there was an error";
            $error = $th->getMessage();
            info("Error", [$th]);
        }
        return [
            "error" => $error,
            "message" => $message,
            "data" => $data,
            "status" => $status
        ];
    }

    private function formatSinglePost(array $posts): array
    {
        $status = false;
        $data = [];
        if ($posts['status']) {
            $postsData = $posts['data'];
            foreach ($postsData as $key => $value) {

                $eachFormattedpostData = $this->formatPostPerCelebrity($value);
                $postObject = new Post();

                $postObject->body = $eachFormattedpostData['postBody'];
                $postObject->title = $eachFormattedpostData['title'];
                $postObject->media_url = json_encode($eachFormattedpostData['media']);
                $postObject->total_likes = $eachFormattedpostData['likeCount'];
                $postObject->total_comments = $eachFormattedpostData['commentCount'];
                $postObject->posted_by = $eachFormattedpostData['user'];
                $postObject->posted_at = $eachFormattedpostData['postedAt'];
                $data[] = $postObject;
                $postObject->save();
            }
            $status = true;
        }
        return ["data" => $data, "status" => $status];
    }


    private function formatArrayOfPosts(array $posts): array
    {
        $data = [];
        foreach ($posts as $post) {

            $postBody = $post->caption->text;
            $postedAt = Carbon::parse($post->caption->created_at);
            $postowner = $post->user->full_name;
            $commentCount = $post->comment_count;
            $likeCount = $post->like_count;
            $media = array_column($post->image_versions2->candidates, "url");

            $postObject = new Post();

            $postObject->body = $postBody;
            $postObject->title = null;
            $postObject->media_url = implode("|", $media);
            $postObject->total_likes = $likeCount;
            $postObject->total_comments = $commentCount;
            $postObject->posted_by = $postowner;
            $postObject->posted_at = $postedAt;
            $data[] = $postObject;
            $postObject->save();
        }
        return $data;
    }

    public function getPosts(Request $request)
    {
        $message = "No post data found";
        $error = "";
        $status = false;
        $data = null;
        $request->validate(['per_page' => ['nullable', 'integer']]);
        try {
            $posts = Post::query();
            if ($request->per_page) {
                $data = $posts->paginate((int) $request->per_page);
            } else {
                $data['data'] = $posts->get();
            }
            if ($posts->count() > 0) {
                $status = true;
                $message =  "Data fetch";
            }
        } catch (Throwable $th) {
            $message = "there was an error";
            $error = $th->getMessage();
        }

        return response()->json([
            "error" => $error,
            "message" => $message,
            "data" => $data,
            "status" => $status
        ]);
    }

    public function getPost($id)
    {
        $message = "no post data found";
        $error = "";
        $status = false;
        $data = null;

        try {
            $post = Post::find($id);
            if ($post) {
                $status = true;
                $message =  "Data fetch";
            }
            $data = $post;
        } catch (Throwable $th) {
            $message = "there was an error";
            $error = $th->getMessage();
        }

        return response()->json([
            "error" => $error,
            "message" => $message,
            "data" => $data,
            "status" => $status
        ]);
    }

    public function deletePost($id)
    {
        $message = "No post data found";
        $error = "";
        $status = false;
        $data = null;

        try {
            $post = Post::find($id);
            if ($post) {
                $post->delete();
                $status = true;
                $message =  "Data Deleted";
            }
            $data = $post;
        } catch (Throwable $th) {
            $message = "there was an error";
            $error = $th->getMessage();
        }

        return response()->json([
            "error" => $error,
            "message" => $message,
            "data" => $data,
            "status" => $status
        ]);
    }

    private function celebsParams(array $celebs)
    {
        $celebOne = [
            "username" => $celebs['0'],
            "depth" => 1,
            "oldest_timestamp" => time(),
            "chunk_size" => 5,
            "start_cursor" => "",
            "token" => $this->token
        ];
        $celebTwo = [
            "username" => $celebs['1'],
            "depth" => 1,
            "oldest_timestamp" => time(),
            "chunk_size" => 5,
            "start_cursor" => "",
            "token" => $this->token
        ];
        $celebThree = [
            "username" => $celebs['2'],
            "depth" => 1,
            "oldest_timestamp" => time(),
            "chunk_size" => 5,
            "start_cursor" => "",
            "token" => $this->token
        ];
        $celebFour = [
            "username" => $celebs['3'],
            "depth" => 1,
            "oldest_timestamp" => time(),
            "chunk_size" => 5,
            "start_cursor" => "",
            "token" => $this->token
        ];

        $celebFive = [
            "username" => $celebs['4'],
            "depth" => 1,
            "oldest_timestamp" => time(),
            "chunk_size" => 5,
            "start_cursor" => "",
            "token" => $this->token
        ];

        return [
            "five" =>  $celebFive,
            "four" => $celebFour,
            "one" => $celebOne,
            "three" => $celebThree,
            "two" => $celebTwo
        ];
    }
}
