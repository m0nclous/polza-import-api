<?php

namespace App\Models\Wp;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermTaxonomy extends Model {
    protected $primaryKey = 'term_taxonomy_id';
    protected $table = 'term_taxonomy';

    public $timestamps = false;

    protected $attributes = [
        'description' => '',
    ];

    protected $fillable = [
        'term_id',
        'taxonomy',
        'parent',
    ];

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    /** @noinspection PhpUnused */
    public function scopeProductType(Builder $query): Builder
    {
        return $query->where('taxonomy', 'product_type');
    }

    /** @noinspection PhpUnused */
    public function scopeProductCat(Builder $query): Builder
    {
        return $query->where('taxonomy', 'product_cat');
    }

    /** @noinspection PhpUnused */
    public function scopeSimpleProduct(Builder $query): Builder
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        return $query->productType()->whereHas('term', fn($term) => $term->where('slug', 'simple'));
    }

    /** @noinspection PhpUnused */
    public function scopeVariableProduct(Builder $query): Builder
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        return $query->productType()->whereHas('term', fn($term) => $term->where('slug', 'variable'));
    }

    /** @noinspection PhpUnused */
    public function scopeUncategorized(Builder $query): Builder
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        return $query->productCat()->whereHas('term', fn($term) => $term->where('slug', 'uncategorized'));
    }
}
