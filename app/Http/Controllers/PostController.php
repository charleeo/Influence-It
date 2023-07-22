<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    private $service;
    public function __construct(PostService $post)
    {
        $this->service = $post;
    }

    public function savePosts(Request $request)
    {
        $request->validate([
            "username" => [
                "required", "string"
            ]
        ]);
        return response()->json($this->service->savePosts($request->username));
    }
    public function saveMultiplePosts(Request $request)
    {
        $request->validate([
            "username" => [
                "required", "array", "min:5"
            ],
            "username.*" => ['required', "string"]
        ]);
        return response()->json($this->service->saveMultiplePosts($request->username));
    }

    public function getPosts(Request $request)
    {
        return $this->service->getPosts($request);
    }

    public function getPost($post)
    {
        return $this->service->getPost($post);
    }

    public function deletePost($post)
    {
        return $this->service->deletePost($post);
    }
}
