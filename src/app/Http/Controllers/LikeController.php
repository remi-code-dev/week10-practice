<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function toggle(Request $request, Post $post)
    {
        // まだいいねしていなければ追加し、すでにいいねしていれば取り消す
        $post->likedUsers()->toggle($request->user()->id);

        return redirect()->route('posts.show', $post);
    }
}
