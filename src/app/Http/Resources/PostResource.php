<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Post
 */
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            // with('user') で読み込まれている時だけ含める（N+1 対策）
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
        ];
    }
}
