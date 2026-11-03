<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Exception;

use Illuminate\Http\Response;

/**
 * A request this read-only service does not accept by its method: a change set in a `$batch`, which carries data
 * modification or action requests only (OData 4.01 Part 1 §11.7).
 */
class MethodNotAllowedException extends ProtocolException
{
    protected $httpCode = Response::HTTP_METHOD_NOT_ALLOWED;
    protected $odataCode = 'method_not_allowed';
    protected $message = 'Method not allowed';
}
