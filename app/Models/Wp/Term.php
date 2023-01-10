<?php

namespace App\Models\Wp;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends Model
{
    use Sluggable;

    protected $primaryKey = 'term_id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function meta(): HasMany
    {
        return $this->hasMany(TermMeta::class, 'term_id');
    }

    public function taxonomies(): HasMany
    {
        return $this->hasMany(TermTaxonomy::class, 'term_id');
    }

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }
}
