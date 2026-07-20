<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service;

/**
 * A single read-authorization message — the standard OData / SAP message shape
 * (`code`, `message`, `numericSeverity`, `target`, `longtextUrl`).
 *
 * `numericSeverity` follows `com.sap.vocabularies.Common.v1.NumericMessageSeverity`:
 * 1 = success, 2 = info, 3 = warning, 4 = error. A root (hard) denial is an error (4)
 * carried in the 403 error envelope; a dropped `$expand` is a warning (3) carried in the
 * `sap-messages` header (the honest-partial model — added in the next slice).
 *
 * **Unbound messages MUST carry an empty `target`** — a non-resolvable target is silently
 * dropped by the UI5 v4 model.
 */
final readonly class ReadMessage
{
    public function __construct(
        public string $code,
        public string $message,
        public int $numericSeverity = 4,
        public string $target = '',
        public ?string $longtextUrl = null,
    ) {}

    public function isError(): bool
    {
        return $this->numericSeverity >= 4;
    }

    /**
     * The standard SAP message array (for the `sap-messages` header — consumed by the
     * honest-partial model in the next slice).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter(
            [
                'code' => $this->code,
                'message' => $this->message,
                'numericSeverity' => $this->numericSeverity,
                'target' => $this->target,
                'longtextUrl' => $this->longtextUrl,
            ],
            static fn ($v) => $v !== null,
        );
    }
}
