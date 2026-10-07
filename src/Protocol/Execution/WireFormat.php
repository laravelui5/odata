<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

/**
 * The JSON format parameters a client asked for, as far as they change the wire.
 *
 * Today that is `IEEE754Compatible`: with `true` the client asks for `Edm.Int64`
 * and `Edm.Decimal` as JSON strings, because a JavaScript number cannot hold
 * them exactly. UI5's V4 model sends it on every request, in the `Accept`
 * header — of the request itself, or of each part inside a `$batch`.
 *
 * @see OData JSON Format v4.01 §4.5.6 (Controlling the Representation of Numbers)
 */
final readonly class WireFormat
{
    private const string CONTENT_TYPE = 'application/json;odata.metadata=minimal';

    public function __construct(public bool $ieee754Compatible = false) {}

    public static function fromAccept(?string $accept): self
    {
        return new self(
            $accept !== null && preg_match('/IEEE754Compatible\s*=\s*"?true"?/i', $accept) === 1,
        );
    }

    /** The response Content-Type, echoing the format parameter when it was applied. */
    public function contentType(): string
    {
        return self::CONTENT_TYPE
            . ($this->ieee754Compatible ? ';IEEE754Compatible=true' : '')
            . ';charset=utf-8';
    }
}
