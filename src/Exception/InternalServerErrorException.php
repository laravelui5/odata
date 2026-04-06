<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Exception;

use Illuminate\Http\Response;

class InternalServerErrorException extends ProtocolException
{
    protected $httpCode = Response::HTTP_INTERNAL_SERVER_ERROR;
    protected $odataCode = 'internal_server_error';
    protected $message = 'Internal server error';
}
