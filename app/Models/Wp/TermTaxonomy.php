<?php

namespace App\Models\Wp;

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
        return $this->belongsTo(Term::class);
    }
}