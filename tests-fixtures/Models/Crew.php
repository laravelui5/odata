<?php

namespace LaravelUi5\OData\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Test fixture: a model declaring its casts the **modern** way — the
 * `casts()` method Laravel has scaffolded since 11.x — rather than the legacy
 * `protected $casts` property that {@see Passenger} uses.
 *
 * Both idioms must produce the same EDM. Discovery reads `getCasts()`, and
 * Eloquent merges `casts()` into `$this->casts` in the constructor — so a model
 * instantiated without its constructor reports none of them. This fixture is the
 * regression guard for that.
 */
class Crew extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'on_duty'  => 'boolean',
            'rank'     => 'integer',
            'hired_at' => 'datetime',
        ];
    }
}
