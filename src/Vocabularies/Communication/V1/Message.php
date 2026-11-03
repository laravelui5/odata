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
     * @param string|Path|null $from Author(s) of the message
     * @param string|Path|null $sender Agent responsible for the actual transmission of the message, e.g. a secretary
     * @param list<string>|Path|null $to List of primary recipients
     * @param list<string>|Path|null $cc List of other recipients (carbon copy)
     * @param list<string>|Path|null $bcc List of recipients whose addresses are not to be revealed (blind carbon copy)
     * @param string|Path|null $subject Topic of the message
     * @param string|Path|null $body Main part of the message
     * @param list<string>|Path|null $keywords List of important words and phrases that might be useful for the recipient
     * @param string|Path|null $received Date and time the message was received
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

    protected function buildAnnotationValue(): AnnotationValueInterface
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
