<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{   
    /**
    *   Display all post in the order of created_at.
    *   And also in desc order then paginate.
    *   @return \Illuminate\Http\Response
    */
    public function index()
    {
        $posts = Post::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->paginate(10)->through(fn($post) =>[
            'id' => $post->id,
            'title' => $post->title,
            'body' => $post->body,
            'excerpt' => $post->excerpt,
            'username' => $post->user->username,
            'category' => $post->categories->pluck('name')->implode(', '),
            'updated_at' => $post->updated_at->format('Y/m/d H:i'),
        ]);

        return response()->json($posts);
    }

    /**
     * Stores new post.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PostRequest $request)
    {  
        if($request->featured_image)
        {
            $exploded = explode(',', $request->featured_image);
            $decoded = base64_decode($exploded[1]);
            $fileName = Str::slug("{$request->title}"). ".jpg";
            $img = Image::make($decoded)->resize(265, 200)->encode('jpg');
            // $fileName = Str::slug("{$request->title}".'.'.'jpg');
            // $img = Image::make($decoded)->resize(265, 200)->encode('jpg');
            $request->merge(['featured_image' => $fileName]);
            Storage::disk('public')->put($fileName,(string) $img);
        }

        if($request->main_image)
        {
            $exploded2 = explode(',', $request->main_image);
            $decoded2 = base64_decode($exploded2[1]);
            $fileName2 = Str::slug("{$request->title}"). ".jpg";
            $img2 = Image::make($decoded2)->resize(736, 530)->encode('jpg');
            $request->merge(['main_image' => $fileName2]);
            Storage::disk('public')->put($fileName2, (string) $img2);
        }

        $post = Post::create([
            'title' => $request->title,
            'body' => $request->body,
            'excerpt' => $request->excerpt,
            'user_id' => auth()->id(),
            'featured_image' => $request->featured_image,
            'main_image' => $request->main_image,
        ]);

        $post->categories()->attach($request->catSelected);
        
        return response()->json([
            'post' => $post,
            'message' => 'Post created successfully.'
        ], 200);
    }

    /**
     * Display/Edit the specified post.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $post = Post::find($id);
        Gate::authorize('edit', $post);
        // $this->authorize('edit', $post);
        $mainCat = Category::all();
        $cat = $post->categories()->get();
        return response()->json([
            'post' => $post, 
            'categories' => $cat,
            'mainCategories' => $mainCat
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function update(PostRequest $request, Post $post)
    {
        Gate::authorize('update', $post);

        $post->update([
            'title' => $request->title,
            'body' => $request->body,
            'excerpt' => $request->excerpt,
        ]);

        // $post->categories()->sync($request->categories);
        // $post->categories()->sync($request->mainCategories);

        return response()->json([
            'post' => $post,
            'message' => 'Post updated successfully.'
        ], 200);
    }

    /**
     * Remove the specified posts.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        $post->delete();
        return response()->json([
            'message' => 'Post deleted successfully.'
        ], 200);
    }
    
    /**
     * Display the deleted posts.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function trashed()
    {
        $posts = Post::onlyTrashed('created_at', 'desc')->paginate(10)->through(fn($post) =>[
            'id' => $post->id,
            'title' => $post->title,
            'body' => $post->body,
            'excerpt' => $post->excerpt,
            'category' => $post->categories->pluck('name')->implode(', '),
            'deleted_at' => $post->deleted_at->format('Y/m/d H:i'),
        ]);

        return response()->json($posts);
    }

    /**
     * Restore the specified posts.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $post = Post::onlyTrashed()->find($id);
        $post->restore();
        return response()->json([
            'message' => 'Post restored successfully.'
        ], 200);
    }

    /**
     * Remove the specified post permanently.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function forceDelete($id)
    {
        $post = Post::onlyTrashed()->find($id);
        $post->categories()->detach();
        $post->forceDelete();
        return response()->json([
            'message' => 'Post deleted successfully.'
        ], 200);
    }
}
