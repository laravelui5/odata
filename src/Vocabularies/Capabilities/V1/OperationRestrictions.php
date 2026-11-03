<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;

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
    ];

    /**
     * @param list<PermissionType>|Path|null $permissions
     * @param list<CustomParameter>|Path|null $customHeaders
     * @param list<CustomParameter>|Path|null $customQueryOptions
     * @param list<HttpResponse>|Path|null $errorResponses
     */
    public function __construct(
        public bool|Path|null $filterSegmentSupported = null,
        public array|Path|null $permissions = null,
        public array|Path|null $customHeaders = null,
        public array|Path|null $customQueryOptions = null,
        public array|Path|null $errorResponses = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new OperationRestrictionsType(
            filterSegmentSupported: $this->filterSegmentSupported,
            permissions: $this->permissions,
            customHeaders: $this->customHeaders,
            customQueryOptions: $this->customQueryOptions,
            errorResponses: $this->errorResponses,
        ))->toRecord();
    }
}
