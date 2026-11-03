<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

/**
 * Email message
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Message implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Communication.v1.Message';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

    /**
     * @param list<string>|Path|null $to
     * @param list<string>|Path|null $cc
     * @param list<string>|Path|null $bcc
     * @param list<string>|Path|null $keywords
     */
    public function __construct(
        public string|Path|null $from = null,
        public string|Path|null $sender = null,
        public array|Path|null $to = null,
        public array|Path|null $cc = null,
        public array|Path|null $bcc = null,
        public string|Path|null $subject = null,
        public string|Path|null $body = null,
        public array|Path|null $keywords = null,
        public string|Path|null $received = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new MessageData(
            from: $this->from,
            sender: $this->sender,
            to: $this->to,
            cc: $this->cc,
            bcc: $this->bcc,
            subject: $this->subject,
            body: $this->body,
            keywords: $this->keywords,
            received: $this->received,
        ))->toRecord();
    }
}
