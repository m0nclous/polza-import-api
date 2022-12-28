<?php

namespace App\Models\Wp;

class Product extends Post {
    protected $primaryKey = 'term_id';
    public $timestamps = false;

    protected $attributes = [
        'post_type' => 'product'
    ];

    protected $fillable = [

    ];

    public function __construct(array $attributes = [])
    {
        $parent = new parent($attributes);
        $this->table = ($parent)->getTable();
        $this->attributes += ($parent)->attributes;
        $this->fillable += ($parent)->fillable;
    }
}