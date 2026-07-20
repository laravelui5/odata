<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Exception;

use Illuminate\Http\Response;
use LaravelUi5\OData\Service\ReadContext;

/**
 * A read was denied by the host's {@see \LaravelUi5\OData\Service\Contracts\ReadAuthorizerInterface}.
 *
 * Serializes as a standard OData 403 error envelope (`{"error": {code, message, target, …}}`)
 * via {@see ProtocolException::toResponse()}. The UI5 v4 model surfaces a failed request's
 * error body to the message model natively — the reliable carrier for a root-set denial (the
 * `sap-messages` header is the carrier for the 200-partial case, not this one).
 */
class ForbiddenException extends ProtocolException
{
    protected $httpCode = Response::HTTP_FORBIDDEN;

    protected $odataCode = 'read_forbidden';

    protected $message = 'Read access denied';

    /**
     * Build a 403 from a {@see ReadContext}'s primary (root) denial, copying its structured
     * message into the OData error envelope.
     */
    public static function fromContext(ReadContext $read): self
    {
        $exception = new self();
        $message = $read->primaryDenial();

        if ($message !== null) {
            $exception->code($message->code)->message($message->message);

            if ($message->target !== '') {
                $exception->target($message->target);
            }
        }

        return $exception;
    }
}
