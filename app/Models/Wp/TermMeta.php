<?php

namespace App\Models\Wp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermMeta extends Model {
    protected $primaryKey = 'meta_id';
    protected $table = 'termmeta';

    public $timestamps = false;

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}