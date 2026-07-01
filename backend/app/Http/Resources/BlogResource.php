<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Full body only on the detail route; lists return the excerpt.
        $isDetail = $request->routeIs('*.blog') || $request->route('blog');

        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'slug'        => $this->slug,
            'excerpt'     => $this->excerpt,
            'body'        => $this->when((bool) $isDetail, $this->body),
            'cover_image' => $this->cover_image,
            'author'      => $this->whenLoaded('author', fn () => [
                'name'   => $this->author?->name,
                'avatar' => $this->author?->avatar,
            ]),
            'published_at' => $this->published_at?->toIso8601String(),
            'url'          => '/blog/' . $this->slug,
        ];
    }
}
