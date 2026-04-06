<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Exception;

use Illuminate\Http\Response;

class NotFoundException extends ProtocolException
{
    protected $httpCode = Response::HTTP_NOT_FOUND;
    protected $odataCode = 'not_found';
    protected $message = 'Not found';
}
