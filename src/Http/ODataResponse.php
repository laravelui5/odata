<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Http;

use LaravelUi5\OData\Exception\ProtocolException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * OData HTTP response — supports both streamed and buffered output
 * with OData error handling.
 *
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358909
 */
class ODataResponse extends StreamedResponse
{
    protected bool $streaming = true;

    public ?Throwable $exception = null;

    public function __construct(?callable $callback = null, int $status = 200, array $headers = [])
    {
        parent::__construct($callback, $status, $headers);

        $this->streaming = config('odata.streaming', true);
    }

    public function sendContent(): static
    {
        if ($this->streaming) {
            $this->headers->set('trailer', 'odata-error');
        }

        return $this->streaming ? $this->sendContentStreamed() : $this->sendContentBuffered();
    }

    private function sendContentStreamed(): static
    {
        try {
            parent::sendContent();
        } catch (ProtocolException $e) {
            flush();
            ob_flush();
            echo 'OData-error: ' . json_encode($e->toError(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }

        return $this;
    }

    /**
     * @throws Throwable
     */
    private function sendContentBuffered(): static
    {
        try {
            ob_start();
            parent::sendContent();
            echo ob_get_clean();
        } catch (ProtocolException $e) {
            ob_end_clean();
            $response = $e->toResponse();
            $this->setStatusCode($response->getStatusCode());
            $this->headers->replace($response->headers->all());
            $response->sendHeaders();
            $response->sendContentBuffered();
            return $response;
        } catch (Throwable $t) {
            ob_end_clean();
            throw $t;
        }

        return $this;
    }

    public function withException(Throwable $e): static
    {
        $this->exception = $e;

        return $this;
    }
}
