<?php

namespace App\Models\Wp;

use Illuminate\Database\Eloquent\Builder;
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
        'post_date' => null,
        'post_date_gmt' => null,
        'post_modified' => null,
        'post_modified_gmt' => null,
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

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $nowGmtFormatted = now(0)->format('Y-m-d H:i:s');
        $nowOmskFormatted = now(6)->format('Y-m-d H:i:s');

        $this->attributes['post_date'] = $this->attributes['post_date'] ?? $nowOmskFormatted;
        $this->attributes['post_date_gmt'] = $this->attributes['post_date_gmt'] ?? $nowGmtFormatted;
        $this->attributes['post_modified'] = $this->attributes['post_modified'] ?? $nowOmskFormatted;
        $this->attributes['post_modified_gmt'] = $this->attributes['post_modified_gmt'] ?? $nowGmtFormatted;
    }

    public function meta(): HasMany
    {
        return $this->hasMany(PostMeta::class, 'post_id');
    }

    public function termRelationships(): HasMany
    {
        return $this->hasMany(TermRelationships::class, 'object_id');
    }

    /** @noinspection PhpUnused */
    public function scopeTypeProduct(Builder $query): Builder
    {
        return $query->where('post_type', 'product');
    }

    /** @noinspection PhpUnused */
    public function scopeTypeVariation(Builder $query): Builder
    {
        return $query->where('post_type', 'product_variation');
    }
}
