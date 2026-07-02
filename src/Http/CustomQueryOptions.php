<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Http;

/**
 * OData **custom query options** — the non-`$`, non-`@` parameters on a request
 * URL (e.g. `?roleCode=customer`).
 *
 * Carried as a property on {@see ODataRequest} (populated by the HTTP entry points
 * from the parsed URL), threaded through the query plan, and handed to an entity
 * set's `query(CustomQueryOptions $options)` by the resolver. Passing it as data —
 * rather than reaching for the global Illuminate request — is what makes it correct
 * under `$batch`: each inner request builds its own `ODataRequest` with its own
 * options, so there is no shared state and nothing to read off the outer envelope.
 */
final readonly class CustomQueryOptions
{
    /** @param array<string, string> $options */
    public function __construct(private array $options = []) {}

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->options[$key] ?? $default;
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->options;
    }

    /**
     * Build from a parsed query map, keeping only custom query options — string
     * keys that don't begin with `$` (system options) or `@` (parameter aliases).
     *
     * @param array<string, mixed> $query
     */
    public static function fromQuery(array $query): self
    {
        $custom = [];

        foreach ($query as $key => $value) {
            if (is_string($key) && $key !== '' && $key[0] !== '$' && $key[0] !== '@' && is_string($value)) {
                $custom[$key] = $value;
            }
        }

        return new self($custom);
    }
}
