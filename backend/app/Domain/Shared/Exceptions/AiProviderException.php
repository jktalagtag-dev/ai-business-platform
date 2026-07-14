<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

/**
 * The upstream OpenAI-compatible endpoint returned an error or an
 * unparsable response. Deliberately its own type (rather than reusing a
 * generic DomainException) so bootstrap/app.php can map it to 502 — the
 * failure is upstream, not a bad request from our own client.
 */
final class AiProviderException extends DomainException {}
