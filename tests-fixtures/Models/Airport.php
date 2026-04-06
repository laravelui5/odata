<?php

namespace LaravelUi5\OData\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function flights()
    {
        return $this->belongsToMany(Flight::class)
            ->withPivot(['role']);
    }
}
