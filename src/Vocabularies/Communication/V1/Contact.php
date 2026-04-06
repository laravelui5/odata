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
 * Address book entry
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Contact implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Communication.v1.Contact';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

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
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Communication.ContactType',
            new PropertyValue('fn', new ConstantAnnotationValue('String', (string) $this->fn)),
            new PropertyValue('n', new ConstantAnnotationValue('String', (string) $this->n)),
            new PropertyValue('nickname', new ConstantAnnotationValue('String', (string) $this->nickname)),
            new PropertyValue('photo', new ConstantAnnotationValue('String', (string) $this->photo)),
            new PropertyValue('bday', new ConstantAnnotationValue('Date', (string) $this->bday)),
            new PropertyValue('anniversary', new ConstantAnnotationValue('Date', (string) $this->anniversary)),
            new PropertyValue('gender', new ConstantAnnotationValue('String', (string) $this->gender)),
            new PropertyValue('title', new ConstantAnnotationValue('String', (string) $this->title)),
            new PropertyValue('role', new ConstantAnnotationValue('String', (string) $this->role)),
            new PropertyValue('org', new ConstantAnnotationValue('String', (string) $this->org)),
            new PropertyValue('orgunit', new ConstantAnnotationValue('String', (string) $this->orgunit)),
            new PropertyValue('kind', new ConstantAnnotationValue('String', (string) $this->kind)),
            new PropertyValue('note', new ConstantAnnotationValue('String', (string) $this->note)),
            new PropertyValue('adr', new ConstantAnnotationValue('String', (string) $this->adr)),
            new PropertyValue('tel', new ConstantAnnotationValue('String', (string) $this->tel)),
            new PropertyValue('email', new ConstantAnnotationValue('String', (string) $this->email)),
            new PropertyValue('geo', new ConstantAnnotationValue('String', (string) $this->geo)),
            new PropertyValue('url', new ConstantAnnotationValue('String', (string) $this->url)),
        );
    }
}
