<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

final readonly class MessageData
{
    public function __construct(
        public readonly array $to,
        public readonly array $cc,
        public readonly array $bcc,
        public readonly array $keywords,
        public readonly ?string $from = null,
        public readonly ?string $sender = null,
        public readonly ?string $subject = null,
        public readonly ?string $body = null,
        public readonly ?string $received = null,
    ) {}
}
