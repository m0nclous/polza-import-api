<?php

namespace App\Models\Wp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermMeta extends Model {
    protected $primaryKey = 'meta_id';
    protected $table = 'termmeta';
    public const META_KEY_GUID = '1c_id';

    public $timestamps = false;

    protected $fillable = [
        'term_id',
        'meta_key',
        'meta_value',
    ];

    public function scopeOfGuid($query, string $guid)
    {
        return $query->where('meta_key', self::META_KEY_GUID)->where('meta_value', $guid)->limit(1);
    }

    public function scopeOfInGuids($query, array $guids)
    {
        return $query->where('meta_key', self::META_KEY_GUID)->whereIn('meta_value', $guids);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}