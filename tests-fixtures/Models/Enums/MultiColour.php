<?php

namespace LaravelUi5\OData\Fixtures\Models\Enums;


enum MultiColour: int
{
    const isFlags = true;

    case Red = 1;
    case Green = 2;
    case Blue = 4;
    case Brown = 8;
}
