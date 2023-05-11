<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    // ----------- [ post listing ] -------------
    public function index()
    {
        $posts      =       Post::latest()->paginate(5);
        return view('pages.icons.material', compact('posts'));
      // return view('index');
    }
    public function showList()
    {
        $posts      =       Post::latest()->paginate(5);
        return view("view-list",compact('posts'));
    }

    // ------------- [ store post ] -----------------
    public function store(Request $request)
    {
        $request->validate([
            'title'         =>      'required',
            'description'   =>      'required',
        ]);

        $post            =           Post::create($request->all());
        $list      =       Post::latest()->paginate(5);
        

        $html_view = view("list",compact('list'))->render();

        if (!is_null($post)) {
            return response()->json(["status" => "success", "message" => "Success! post created.", "data" => $post,"body"=> $html_view ]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! post not created"]);
        }
    }

    // ---------------- [ Update post ] -------------
    public function update(Request $request)
    {
        $post_id        =       $request->id;
        $post           =       Post::where("id", $post_id)->update($request->all());
        $list      =       Post::latest()->paginate(5);
        $html_view = view("list",compact('list'))->render();
        if ($post == 1) {
            return response()->json(["status" => "success", "message" => "Success! post updated","body"=> $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! post not updated"]);
        }
    }

    // -------------- [ Delete post ] ---------------
    public function destroy($post_id)
    {
        $post       =       Post::where("id", $post_id)->delete();
        $list      =       Post::latest()->paginate(5);
        $html_view = view("list",compact('list'))->render();
        if ($post == 1) {
            return response()->json(["status" => "success", "message" => "Success! post deleted","body"=> $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! post not deleted"]);
        }
    }
}
