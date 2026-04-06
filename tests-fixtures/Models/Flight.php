<?php

namespace LaravelUi5\OData\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;


class Flight extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'duration' => 'float',
    ];

    public function passengers()
    {
        return $this->hasMany(Passenger::class);
    }

    public function airports()
    {
        return $this->belongsToMany(Airport::class)
            ->withPivot(['role']);
    }

    public function originAirport()
    {
        return $this->hasOne(Airport::class, 'code', 'origin');
    }

    public function destinationAirport()
    {
        return $this->hasOne(Airport::class, 'code', 'destination');
    }
}

