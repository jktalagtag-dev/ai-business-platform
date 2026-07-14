<?php

declare(strict_types=1);

namespace App\Http\Support;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Pagination\Cursor;

/**
 * Laravel's cursor pagination computes next/previous cursors by reading the
 * ORDER BY column(s) directly off each item — see
 * Illuminate\Pagination\AbstractCursorPaginator::getParametersForItem().
 * That works for Eloquent models, but repositories in this codebase call
 * ->through() to map paginated Eloquent models into plain Domain entities
 * before returning (Services/Controllers must never see Eloquent models —
 * see ARCHITECTURE.md's Repository Pattern rule). Once that mapping has
 * happened, the Domain entity often doesn't expose a property matching the
 * SQL column name (e.g. Employee has no $created_at), and cursor computation
 * throws.
 *
 * The fix: capture the real cursors from the paginator *before* ->through()
 * runs (while items are still Eloquent models), then wrap the transformed
 * paginator in this decorator so callers keep getting a normal
 * CursorPaginator — only previousCursor()/nextCursor() are overridden to
 * return the pre-captured values instead of recomputing from the (by then
 * Domain-entity-shaped) items.
 */
final class CachedCursorPaginator implements CursorPaginator
{
    public function __construct(
        private readonly CursorPaginator $paginator,
        private readonly ?Cursor $cachedNextCursor,
        private readonly ?Cursor $cachedPreviousCursor,
    ) {}

    /**
     * Captures cursors from $paginator (while its items are still Eloquent
     * models), then applies $mapper via ->through() and wraps the result.
     * Drop-in replacement for `$paginator->through($mapper)` wherever the
     * mapper's output type won't carry the ORDER BY column(s) as properties.
     */
    public static function wrap(CursorPaginator $paginator, callable $mapper): self
    {
        $nextCursor = $paginator->nextCursor();
        $previousCursor = $paginator->previousCursor();

        return new self($paginator->through($mapper), $nextCursor, $previousCursor);
    }

    public function previousCursor()
    {
        return $this->cachedPreviousCursor;
    }

    public function nextCursor()
    {
        return $this->cachedNextCursor;
    }

    public function __call($name, $arguments)
    {
        return $this->paginator->{$name}(...$arguments);
    }

    public function url($cursor)
    {
        return $this->paginator->url($cursor);
    }

    public function appends($key, $value = null)
    {
        return $this->paginator->appends($key, $value);
    }

    public function fragment($fragment = null)
    {
        return $this->paginator->fragment($fragment);
    }

    public function withQueryString()
    {
        return $this->paginator->withQueryString();
    }

    public function previousPageUrl()
    {
        return $this->paginator->previousPageUrl();
    }

    public function nextPageUrl()
    {
        return $this->paginator->nextPageUrl();
    }

    public function items()
    {
        return $this->paginator->items();
    }

    public function perPage()
    {
        return $this->paginator->perPage();
    }

    public function cursor()
    {
        return $this->paginator->cursor();
    }

    public function hasPages()
    {
        return $this->paginator->hasPages();
    }

    public function hasMorePages()
    {
        return $this->paginator->hasMorePages();
    }

    public function path()
    {
        return $this->paginator->path();
    }

    public function isEmpty()
    {
        return $this->paginator->isEmpty();
    }

    public function isNotEmpty()
    {
        return $this->paginator->isNotEmpty();
    }

    public function render($view = null, $data = [])
    {
        return $this->paginator->render($view, $data);
    }
}
