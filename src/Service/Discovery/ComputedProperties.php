<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Discovery;

use Illuminate\Database\Eloquent\Model;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataComputed;
use ReflectionClass;

/**
 * The computed properties a model declares with `#[ODataComputed]`, read once per class.
 *
 * @internal discovery declares them, the Eloquent resolver selects them
 */
final class ComputedProperties
{
    /** @var array<class-string<Model>, array<string, ODataComputed>> */
    private static array $cache = [];

    /**
     * @param class-string<Model> $modelClass
     * @return array<string, ODataComputed> by property name
     */
    public static function of(string $modelClass): array
    {
        if (!isset(self::$cache[$modelClass])) {
            $computed = [];
            foreach ((new ReflectionClass($modelClass))->getAttributes(ODataComputed::class) as $attribute) {
                $declaration                  = $attribute->newInstance();
                $computed[$declaration->name] = $declaration;
            }
            self::$cache[$modelClass] = $computed;
        }

        return self::$cache[$modelClass];
    }
}
