<?php

namespace App\Models\Wp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model {
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $attributes = [
        'post_author' => 0,
        'post_content' => '',
        'post_title' => '',
        'post_excerpt' => '',
        'post_status' => 'publish',
        'comment_status' => 'open',
        'ping_status' => 'open',
        'post_password' => '',
        'post_name' => '',
        'to_ping' => '',
        'pinged' => '',
        'post_content_filtered' => '',
        'post_parent' => 0,
        'guid' => '',
        'menu_order' => 0,
        'post_type' => 'post',
        'post_mime_type' => '',
        'comment_count' => 0,
    ];

    protected $fillable = [
        'post_title',
        'post_name',
        'guid',
        'post_content',
        'post_date',
        'post_date_gmt',
        'post_modified',
        'post_modified_gmt',
    ];

    public function meta(): HasMany
    {
        return $this->hasMany(PostMeta::class, 'post_id');
    }

    public function termRelationships(): HasMany
    {
        return $this->hasMany(TermRelationships::class, 'object_id');
    }
}