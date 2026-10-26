<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    /**
     * マスアサインメント可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'content',
        'category_id',
        // S3に保存した画像のパス。create($data) / update($data) でまとめて保存できるように追加
        'image_path',
    ];

    /**
     * この投稿が属するカテゴリー
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * この投稿の投稿者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
