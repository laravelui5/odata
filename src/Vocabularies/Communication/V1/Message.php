<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

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
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Communication.MessageData',
            new PropertyValue('from', new ConstantAnnotationValue('String', (string) $this->from)),
            new PropertyValue('sender', new ConstantAnnotationValue('String', (string) $this->sender)),
            new PropertyValue('to', new ConstantAnnotationValue('String', (string) $this->to)),
            new PropertyValue('cc', new ConstantAnnotationValue('String', (string) $this->cc)),
            new PropertyValue('bcc', new ConstantAnnotationValue('String', (string) $this->bcc)),
            new PropertyValue('subject', new ConstantAnnotationValue('String', (string) $this->subject)),
            new PropertyValue('body', new ConstantAnnotationValue('String', (string) $this->body)),
            new PropertyValue('keywords', new ConstantAnnotationValue('String', (string) $this->keywords)),
            new PropertyValue('received', new ConstantAnnotationValue('DateTimeOffset', (string) $this->received)),
        );
    }
}
