<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service;

/**
 * The read-side collector — the read analog of a write-side transaction seal.
 *
 * A single read can touch many targets (a root set plus its `$expand` navigation
 * targets). A {@see \LaravelUi5\OData\Service\Contracts\ReadAuthorizerInterface} records
 * a verdict per target into this collector; the controller / engine then reads it to shape
 * the response:
 *
 *   - a **hard denial** (a primary / root target) → the controller answers a 403
 *     {@see \LaravelUi5\OData\Exception\ForbiddenException};
 *   - a **drop** (an `$expand` target) → {@see \LaravelUi5\OData\Http\ReadGate} prunes it from
 *     the plan and emits a `sap-messages` warning on the 200 (the honest-partial model);
 *   - no verdict recorded → the read proceeds.
 *
 * The default {@see AllowAllReadAuthorizer} records nothing, so an unconfigured OData
 * proceeds exactly as before.
 */
final class ReadContext
{
    /** @var list<array{target: string, message: ReadMessage}> */
    private array $hardDenials = [];

    /** @var list<array{target: string, message: ReadMessage}> */
    private array $drops = [];

    /**
     * Record that a target may be read. A no-op for response shaping; present for
     * symmetry so an enforcer can be explicit.
     */
    public function allow(string $target): void
    {
    }

    /** Record a hard denial (a primary / root target) → the controller answers 403. */
    public function denyHard(string $target, ReadMessage $message): void
    {
        $this->hardDenials[] = ['target' => $target, 'message' => $message];
    }

    /**
     * Record a droppable (`$expand`) denial → pruned from the plan + a `sap-messages`
     * warning on the 200 (the honest-partial model, {@see \LaravelUi5\OData\Http\ReadGate}).
     */
    public function denyDrop(string $target, ReadMessage $message): void
    {
        $this->drops[] = ['target' => $target, 'message' => $message];
    }

    public function hasHardDenial(): bool
    {
        return $this->hardDenials !== [];
    }

    /** @return list<array{target: string, message: ReadMessage}> */
    public function hardDenials(): array
    {
        return $this->hardDenials;
    }

    /** The first hard denial's message — the headline for the 403 error envelope. */
    public function primaryDenial(): ?ReadMessage
    {
        return $this->hardDenials[0]['message'] ?? null;
    }

    /** @return list<string> the dropped `$expand` target paths, which the read gate prunes. */
    public function dropped(): array
    {
        return array_column($this->drops, 'target');
    }

    /** @return list<ReadMessage> the drop messages, carried in the `sap-messages` header. */
    public function dropMessages(): array
    {
        return array_column($this->drops, 'message');
    }
}
