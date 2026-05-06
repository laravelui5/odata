<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Container\EnumType;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Fixtures\Models\Enums\Colour;
use LaravelUi5\OData\Fixtures\Models\Enums\Direction;
use LaravelUi5\OData\Fixtures\Models\Enums\Status;

describe('EnumType::fromBackedEnum', function () {

    it('builds an EnumType from an int-backed PHP enum', function () {
        $type = EnumType::fromBackedEnum('Test.Service', Colour::class);

        expect($type->getName())->toBe('Colour');
        expect($type->getQualifiedName())->toBe('Test.Service.Colour');
        expect($type->getUnderlyingType())->toBe(EdmPrimitiveType::Int32);
        expect($type->isFlags())->toBeFalse();
    });

    it('emits members in declaration order with PHP case names and integer values', function () {
        $type = EnumType::fromBackedEnum('Test.Service', Colour::class);

        $members = $type->getMembers();
        expect($members)->toHaveCount(4);

        expect($members[0]->getName())->toBe('Red');
        expect($members[0]->getValue())->toBe(1);

        expect($members[1]->getName())->toBe('Green');
        expect($members[1]->getValue())->toBe(2);

        expect($members[2]->getName())->toBe('Blue');
        expect($members[2]->getValue())->toBe(4);

        expect($members[3]->getName())->toBe('Brown');
        expect($members[3]->getValue())->toBe(8);
    });

    it('looks up a member by name', function () {
        $type   = EnumType::fromBackedEnum('Test.Service', Colour::class);
        $member = $type->getMember('Green');

        expect($member)->not->toBeNull();
        expect($member->getValue())->toBe(2);
    });

    it('throws on a non-existent class-string', function () {
        expect(fn () => EnumType::fromBackedEnum('Test.Service', 'App\\Does\\Not\\Exist'))
            ->toThrow(\InvalidArgumentException::class, 'expected a PHP enum class-string');
    });

    it('throws on a pure (non-backed) enum', function () {
        expect(fn () => EnumType::fromBackedEnum('Test.Service', Direction::class))
            ->toThrow(\InvalidArgumentException::class, 'expected a backed enum, got pure enum');
    });

    it('throws on a string-backed enum', function () {
        expect(fn () => EnumType::fromBackedEnum('Test.Service', Status::class))
            ->toThrow(\InvalidArgumentException::class, 'expected an int-backed enum, got string-backed');
    });
});
