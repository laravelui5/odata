<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning\Expression;

enum FilterExpressionKind
{
    case Literal;
    case NullLiteral;
    case PropertyPath;
    case Binary;
    case Unary;
    case FunctionCall;
    case Lambda;
    case LambdaVariable;
}
