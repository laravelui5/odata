<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Restrictions for function or action operation
 * @see TypedAnnotationInterface
 */
final readonly class OperationRestrictions implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.OperationRestrictions';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        FunctionInterface::class,
        FunctionInterface::class,
    ];

    public function __construct(
        public readonly bool $filterSegmentSupported,
        public readonly array $customHeaders,
        public readonly array $customQueryOptions,
        public readonly array $errorResponses,
        public readonly ?array $permissions = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Capabilities.OperationRestrictionsType',
            new PropertyValue('FilterSegmentSupported', new ConstantAnnotationValue('Boolean', (string) $this->filterSegmentSupported)),
            new PropertyValue('Permissions', new ConstantAnnotationValue('String', (string) $this->permissions)),
            new PropertyValue('CustomHeaders', new ConstantAnnotationValue('String', (string) $this->customHeaders)),
            new PropertyValue('CustomQueryOptions', new ConstantAnnotationValue('String', (string) $this->customQueryOptions)),
            new PropertyValue('ErrorResponses', new ConstantAnnotationValue('String', (string) $this->errorResponses)),
        );
    }
}
