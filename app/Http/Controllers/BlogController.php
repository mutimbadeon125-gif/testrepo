<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    /**
     * GET /blog/posts (public)
     */
    public function index(Request $request)
    {
        $query = BlogPost::with('user')
            ->where('status', 'published');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $posts = $query
            ->orderByDesc('published_at')
            ->paginate($request->limit ?? 10);

        return response()->json([
            'data' => $posts->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ]
        ]);
    }

    /**
     * GET /blog/posts/{id}
     */
    public function show($id)
    {
        $blog = BlogPost::with('user')->find($id);

        if (!$blog || $blog->status !== 'published') {
            return response()->json(['message' => 'Blog not found'], 404);
        }

        // Safe view increment
        if ($blog->views_count === null) {
            $blog->update(['views_count' => 1]);
        } else {
            $blog->increment('views_count');
        }

        return response()->json($blog);
    }

    /**
     * POST /blog/posts (admin or vendor)
     */
    public function store(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'vendor'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'excerpt'        => 'nullable|string',
            'content'        => 'required|string',
            'featured_image' => 'nullable|string',
            'category'       => 'required|string|max:100',
            'tags'           => 'nullable|array',
            'status'         => 'nullable|in:draft,published',
        ]);

        $status = $data['status'] ?? 'published';

        $blog = BlogPost::create([
            'user_id'        => auth()->id(),
            'title'          => $data['title'],
            'slug'           => Str::slug($data['title']) . '-' . Str::random(4),
            'excerpt'        => $data['excerpt'] ?? null,
            'content'        => $data['content'],
            'featured_image' => $data['featured_image'] ?? null,
            'category'       => $data['category'],
            'tags'           => $data['tags'] ?? [],
            'status'         => $status,
            'author'         => auth()->user()->name,
            'published_at'   => $status === 'published' ? now() : null,
            'views_count'    => 0,
        ]);

        return response()->json([
            'message' => 'Blog created successfully',
            'blog' => $blog
        ], 201);
    }

    /**
     * PUT /blog/posts/{id}
     */
    public function update(Request $request, $id)
    {
        $blog = BlogPost::find($id);

        if (!$blog) {
            return response()->json(['message' => 'Blog not found'], 404);
        }

        if (auth()->user()->role !== 'admin' && auth()->id() !== $blog->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'title'          => 'sometimes|string|max:255',
            'excerpt'        => 'sometimes|string',
            'content'        => 'sometimes|string',
            'featured_image' => 'sometimes|string',
            'category'       => 'sometimes|string|max:100',
            'tags'           => 'sometimes|array',
            'status'         => 'sometimes|in:draft,published',
        ]);

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']) . '-' . Str::random(4);
        }

        if (
            isset($data['status']) &&
            $data['status'] === 'published' &&
            !$blog->published_at
        ) {
            $data['published_at'] = now();
        }

        $blog->update($data);

        return response()->json([
            'message' => 'Blog updated successfully',
            'blog' => $blog
        ]);
    }

    /**
     * DELETE /blog/posts/{id}
     */
    public function destroy($id)
    {
        $blog = BlogPost::find($id);

        if (!$blog) {
            return response()->json(['message' => 'Blog not found'], 404);
        }

        if (auth()->user()->role !== 'admin' && auth()->id() !== $blog->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $blog->delete();

        return response()->json(['message' => 'Blog deleted']);
    }
}
