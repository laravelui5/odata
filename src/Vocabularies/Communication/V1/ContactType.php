<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

final readonly class ContactType
{
    public function __construct(
        public readonly array $adr,
        public readonly array $tel,
        public readonly array $email,
        public readonly array $geo,
        public readonly array $url,
        public readonly ?string $fn = null,
        public readonly mixed $n = null,
        public readonly ?string $nickname = null,
        public readonly ?string $photo = null,
        public readonly ?string $bday = null,
        public readonly ?string $anniversary = null,
        public readonly mixed $gender = null,
        public readonly ?string $title = null,
        public readonly ?string $role = null,
        public readonly ?string $org = null,
        public readonly ?string $orgunit = null,
        public readonly mixed $kind = null,
        public readonly ?string $note = null,
    ) {}
}
