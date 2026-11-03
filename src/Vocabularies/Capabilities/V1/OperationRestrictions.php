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
     * @param bool|Path|null $filterSegmentSupported Bound action or function can be invoked on a collection-valued binding parameter path with a `/$filter(...)` segment
     * @param list<PermissionType>|Path|null $permissions Required permissions. One of the specified sets of scopes is required to invoke an action or function
     * @param list<CustomParameter>|Path|null $customHeaders Supported or required custom headers
     * @param list<CustomParameter>|Path|null $customQueryOptions Supported or required custom query options
     * @param list<HttpResponse>|Path|null $errorResponses Possible error responses returned by the request.
     */
    public function __construct(
        public bool|Path|null $filterSegmentSupported = null,
        public array|Path|null $permissions = null,
        public array|Path|null $customHeaders = null,
        public array|Path|null $customQueryOptions = null,
        public array|Path|null $errorResponses = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): AnnotationValueInterface
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
