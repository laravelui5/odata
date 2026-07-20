<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Exception;

use LaravelUi5\OData\Http\ODataResponse;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\App;
use RuntimeException;
use Throwable;

/**
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_ErrorResponseBody
 */
abstract class ProtocolException extends RuntimeException implements Responsable
{
    protected $httpCode = ODataResponse::HTTP_INTERNAL_SERVER_ERROR;
    protected $odataCode;
    protected $message;
    protected $target = null;
    protected $details = [];
    protected $innerError = [];
    protected $headers = [];
    protected $suppressContent = false;
    protected $originalException = null;

    public function __construct(?string $code = null, ?string $message = null, ?Throwable $originalException = null)
    {
        if ($code) {
            $this->odataCode = $code;
        }

        if ($message) {
            $this->message = $message;
        }

        if ($originalException) {
            $this->originalException = $originalException;
        }

        parent::__construct($this->message);
    }

    /**
     * Control whether Laravel's exception handler logs this exception.
     *
     * A `4xx` OData error is an **expected client outcome** (a malformed query, an
     * unauthorized read, an unknown set) — not a server fault — so it must not spam the
     * error log with a stack trace. A `5xx` is a genuine engine failure and is reported.
     *
     * The return value follows Laravel's `report()` contract (Foundation `Handler`):
     * a **non-false** return means "handled — skip default logging"; a **false** return
     * lets the handler perform its normal error logging. Hence: `4xx → true` (suppressed),
     * `5xx → false` (logged). The HTTP response is unchanged either way — this governs
     * server-side logging only.
     */
    public function report(): bool
    {
        if ($this->httpCode < 500) {
            return true;  // expected client outcome → suppress default logging
        }

        return false;     // genuine server error → let Laravel log it
    }

    /**
     * Set the OData error code
     * @param  string  $code  Code
     * @return $this
     */
    public function code(string $code): self
    {
        $this->odataCode = $code;
        return $this;
    }

    /**
     * Set the OData error message
     * @param  string  $message  Message
     * @return $this
     */
    public function message(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Set the OData error target
     * @param  string  $target  Target
     * @return $this
     */
    public function target(string $target): self
    {
        $this->target = $target;
        return $this;
    }

    /**
     * Set the OData error details
     * @param  string  $code  Details
     * @return $this
     */
    public function addDetail(string $code, string $message, ?string $target = null): self
    {
        $detail = [
            'code' => $code,
            'message' => $message,
        ];

        if ($target) {
            $detail['target'] = $target;
        }

        $this->details[] = $detail;

        return $this;
    }

    /**
     * Set the OData inner error
     * @param  string  $key  Key
     * @param  string  $value  Value
     * @return $this
     */
    public function addInnerError(string $key, string $value): self
    {
        $this->innerError[$key] = $value;

        return $this;
    }

    /**
     * Set a header on the outgoing response
     * @param  string  $key  Key
     * @param  string  $value  Value
     * @return $this
     */
    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Serialize this error
     * @return array
     */
    public function serialize()
    {
        return array_filter([
            'httpCode' => $this->httpCode,
            'odataCode' => $this->odataCode,
            'message' => $this->message,
            'target' => $this->target,
            'details' => $this->details,
            'innererror' => $this->innerError,
            'headers' => $this->headers,
        ]);
    }

    /**
     * Convert this exception to a Symfony error
     * @return array
     */
    public function toError()
    {
        return [
            'code' => $this->odataCode,
            'message' => $this->message,
            'target' => $this->target,
            'details' => $this->details,
            'innererror' => $this->innerError ?: (object) [],
        ];
    }

    /**
     * Get the original exception that caused this exception
     *
     * @return Throwable|null
     */
    public function getOriginalException(): ?Throwable
    {
        return $this->originalException;
    }

    /**
     * Convert this exception to a Symfony response
     * @param  null  $request  Request
     * @return ODataResponse Response
     */
    public function toResponse($request = null): ODataResponse
    {
        $response = App::make(ODataResponse::class);

        $response->setCallback(function () {
            if ($this->suppressContent) {
                return;
            }

            echo json_encode(['error' => $this->toError()], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        });

        $response->setProtocolVersion('1.1');
        $response->setStatusCode($this->httpCode);
        $response->headers->replace($this->headers);
        $response->headers->set('content-type', 'application/json');

        return $response;
    }

    public function getInnerException(): ?Throwable
    {
        return $this->originalException;
    }
}
