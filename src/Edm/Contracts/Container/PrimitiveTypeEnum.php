<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Container;

/**
 * All built-in primitive types defined by the OData Edm.
 *
 * These are the leaf types of the type system — they carry no
 * further structure and are identified solely by their name.
 * Every structural property whose type is not an EntityType,
 * ComplexType, EnumType, or TypeDefinition resolves to one of
 * these values.
 *
 * The string value of each case is the fully qualified Edm name
 * as it appears in CSDL documents, e.g. "Edm.String".
 *
 * @see OData CSDL XML v4.01 §3.3 (Primitive Types)
 */
enum PrimitiveTypeEnum: string
{
    case Binary          = 'Edm.Binary';
    case Boolean         = 'Edm.Boolean';
    case Byte            = 'Edm.Byte';
    case Date            = 'Edm.Date';
    case DateTimeOffset  = 'Edm.DateTimeOffset';
    case Decimal         = 'Edm.Decimal';
    case Double          = 'Edm.Double';
    case Duration        = 'Edm.Duration';
    case Guid            = 'Edm.Guid';
    case Int16           = 'Edm.Int16';
    case Int32           = 'Edm.Int32';
    case Int64           = 'Edm.Int64';
    case SByte           = 'Edm.SByte';
    case Single          = 'Edm.Single';
    case Stream          = 'Edm.Stream';
    case String          = 'Edm.String';
    case TimeOfDay       = 'Edm.TimeOfDay';

    /**
     * Geography types (spatial, WGS84 reference system by default).
     *
     * @see OData CSDL XML v4.01 §3.3
     */
    case Geography           = 'Edm.Geography';
    case GeographyPoint      = 'Edm.GeographyPoint';
    case GeographyLineString = 'Edm.GeographyLineString';
    case GeographyPolygon    = 'Edm.GeographyPolygon';
    case GeographyMultiPoint      = 'Edm.GeographyMultiPoint';
    case GeographyMultiLineString = 'Edm.GeographyMultiLineString';
    case GeographyMultiPolygon    = 'Edm.GeographyMultiPolygon';
    case GeographyCollection      = 'Edm.GeographyCollection';

    /**
     * Geometry types (spatial, flat-earth / arbitrary reference system).
     *
     * @see OData CSDL XML v4.01 §3.3
     */
    case Geometry           = 'Edm.Geometry';
    case GeometryPoint      = 'Edm.GeometryPoint';
    case GeometryLineString = 'Edm.GeometryLineString';
    case GeometryPolygon    = 'Edm.GeometryPolygon';
    case GeometryMultiPoint      = 'Edm.GeometryMultiPoint';
    case GeometryMultiLineString = 'Edm.GeometryMultiLineString';
    case GeometryMultiPolygon    = 'Edm.GeometryMultiPolygon';
    case GeometryCollection      = 'Edm.GeometryCollection';
}
