<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogResource;
use App\Http\Resources\BranchResource;
use App\Http\Resources\FaqResource;
use App\Http\Resources\TeamResource;
use App\Models\Blog;
use App\Models\Branch;
use App\Models\Faq;
use App\Models\Team;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

/**
 * Public marketing content: blogs, FAQs, branches, team.
 */
class ContentController extends Controller
{
    /** GET /api/v1/blogs */
    public function blogs(Request $request)
    {
        $blogs = Blog::published()
            ->with('author')
            ->latest('published_at')
            ->paginate(min((int) $request->integer('per_page', 9), 30));

        return ApiResponse::success(BlogResource::collection($blogs), 'Blogs retrieved.');
    }

    /** GET /api/v1/blogs/{blog} — bound by slug. */
    public function blog(Blog $blog)
    {
        abort_unless($blog->published_at !== null && $blog->published_at <= now(), 404);

        return ApiResponse::success(new BlogResource($blog->load('author')), 'Blog retrieved.');
    }

    /** GET /api/v1/faqs */
    public function faqs()
    {
        $faqs = Faq::where('is_active', true)->orderBy('sort_order')->get();

        return ApiResponse::success(FaqResource::collection($faqs), 'FAQs retrieved.');
    }

    /** GET /api/v1/branches */
    public function branches()
    {
        $branches = Branch::with('city')->orderByDesc('is_head_office')->get();

        return ApiResponse::success(BranchResource::collection($branches), 'Branches retrieved.');
    }

    /** GET /api/v1/team */
    public function team()
    {
        $team = Team::with('branch')->orderBy('sort_order')->get();

        return ApiResponse::success(TeamResource::collection($team), 'Team retrieved.');
    }
}
