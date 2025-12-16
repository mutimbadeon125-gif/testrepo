<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BlogPost extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'user_id',
        'title',
        'excerpt',
        'content',
        'featured_image',
        'category',
        'slug',
        'author',
        'author_bio',
        'tags',
        'status',
        'published_at',
        'views_count'
    ];

    protected $casts = [
        'tags' => 'array',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
