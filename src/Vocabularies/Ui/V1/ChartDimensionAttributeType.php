<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ChartDimensionAttributeType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.ChartDimensionAttributeType';

    public const array FIELDS = [
        'Dimension' => ['dimension', 'Edm.PropertyPath', false],
        'Role' => ['role', 'com.sap.vocabularies.UI.v1.ChartDimensionRoleType', false],
        'HierarchyLevel' => ['hierarchyLevel', 'Edm.Int32', false],
        'ValuesForSequentialColorLevels' => ['valuesForSequentialColorLevels', 'Collection(Edm.String)', false],
        'EmphasizedValues' => ['emphasizedValues', 'Collection(Edm.String)', false],
        'EmphasisLabels' => ['emphasisLabels', 'com.sap.vocabularies.UI.v1.EmphasisLabelType', false],
    ];

    /**
     * @param int|Path|null $hierarchyLevel For a dimension with a hierarchy, members are selected from this level. The root node of the hierarchy is at level 0.
     * @param list<string>|Path|null $valuesForSequentialColorLevels All values in this collection should be assigned to levels of the same color.
     * @param list<string>|Path|null $emphasizedValues All values in this collection should be emphasized.
     * @param EmphasisLabelType|Path|null $emphasisLabels Assign a label to values with an emphasized representation. This is required, if more than one emphasized value has been specified.
     */
    public function __construct(
        public string|Path|null $dimension = null,
        public ChartDimensionRoleType|Path|null $role = null,
        public int|Path|null $hierarchyLevel = null,
        public array|Path|null $valuesForSequentialColorLevels = null,
        public array|Path|null $emphasizedValues = null,
        public EmphasisLabelType|Path|null $emphasisLabels = null,
    ) {}
}
