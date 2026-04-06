<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning\Expression;

enum BinaryOperator
{
    // Comparison
    case Eq;
    case Ne;
    case Gt;
    case Ge;
    case Lt;
    case Le;

    // Logical
    case And;
    case Or;

    // Arithmetic
    case Add;
    case Sub;
    case Mul;
    case Div;
    case DivBy;
    case Mod;

    // Collection
    case Has;
    case In;
}
