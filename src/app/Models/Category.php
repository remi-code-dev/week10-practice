<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    /**
     * マスアサインメント可能な属性
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * このカテゴリーに属する投稿
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
