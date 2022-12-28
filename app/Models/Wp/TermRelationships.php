<?php

namespace App\Models\Wp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TermRelationships extends Model {
    public $timestamps = false;

    protected $fillable = [
        'object_id',
        'term_taxonomy_id',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'object_id');
    }

    public function taxonomies(): HasMany
    {
        return $this->hasMany(TermTaxonomy::class, 'term_taxonomy_id');
    }
}