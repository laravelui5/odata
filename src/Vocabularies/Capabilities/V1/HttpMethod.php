<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

enum HttpMethod: int
{
    // IsFlags = true
    case GET = 1;
    case PATCH = 2;
    case PUT = 4;
    case POST = 8;
    case DELETE = 16;
    case OPTIONS = 32;
    case HEAD = 64;
}
