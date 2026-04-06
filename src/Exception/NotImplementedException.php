<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Exception;

use Illuminate\Http\Response;

class NotImplementedException extends ProtocolException
{
    protected $httpCode = Response::HTTP_NOT_IMPLEMENTED;
    protected $odataCode = 'not_implemented';
    protected $message = 'Not implemented';
}
