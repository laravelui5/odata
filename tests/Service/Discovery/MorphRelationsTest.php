<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Discovery\ModelDiscovery;
use LaravelUi5\OData\Tests\TestCase;

/*
 * OP20 / OP21 (decided 2026-10-07): discovery leaves every polymorphic relation
 * out. MorphTo and MorphToMany used to slip through as ordinary navigations,
 * because they extend BelongsTo / BelongsToMany.
 */

uses(TestCase::class);

class MorphPost extends Model
{
    protected $table = 'morph_posts';

    public function author(): BelongsTo { return $this->belongsTo(MorphAuthor::class, 'author_id'); }
    public function comments(): MorphMany { return $this->morphMany(MorphComment::class, 'commentable'); }
    public function cover(): MorphOne { return $this->morphOne(MorphComment::class, 'commentable'); }
    public function tags(): MorphToMany { return $this->morphToMany(MorphTag::class, 'taggable'); }
}

class MorphComment extends Model
{
    protected $table = 'morph_comments';

    public function commentable(): MorphTo { return $this->morphTo(); }
}

class MorphTag extends Model
{
    protected $table = 'morph_tags';

    public function posts(): MorphToMany { return $this->morphedByMany(MorphPost::class, 'taggable'); }
}

class MorphAuthor extends Model
{
    protected $table = 'morph_authors';
}

beforeEach(function () {
    DB::statement('CREATE TABLE morph_posts (id integer primary key, author_id integer)');
    DB::statement('CREATE TABLE morph_comments (id integer primary key, commentable_type varchar(80), commentable_id integer)');
    DB::statement('CREATE TABLE morph_tags (id integer primary key)');
    DB::statement('CREATE TABLE morph_authors (id integer primary key)');
});

it('leaves every polymorphic relation out and keeps the regular ones', function () {
    $discovery = new ModelDiscovery();
    foreach ([MorphPost::class, MorphComment::class, MorphTag::class, MorphAuthor::class] as $model) {
        $discovery->add($model);
    }
    $builder = (new EdmBuilder())->namespace('Test.Ns');
    $discovery->apply($builder, 'Test.Ns');
    $schema = $builder->build()->getSchema('Test.Ns');

    $navs = fn (string $type) => array_map(
        fn ($nav) => $nav->getName(),
        $schema->getEntityType($type)->getDeclaredNavigationProperties(),
    );

    expect($navs('MorphPost'))->toBe(['author'])      // not comments, cover, tags
        ->and($navs('MorphComment'))->toBe([])          // not commentable (MorphTo)
        ->and($navs('MorphTag'))->toBe([]);             // not posts (morphedByMany)
});
