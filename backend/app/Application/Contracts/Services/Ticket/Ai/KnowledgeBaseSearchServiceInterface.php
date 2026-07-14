<?php

declare(strict_types=1);

namespace App\Application\Contracts\Services\Ticket\Ai;

/**
 * AI preparation only. No Knowledge Base module exists yet (it was
 * sketched in DATABASE.md's original design as `knowledge_base_articles` +
 * `embeddings`, per ARCHITECTURE.md §9's RAG approach, but has not been
 * built). Once it is, this interface lets TicketService surface relevant
 * KB articles alongside a ticket without TicketService depending on the
 * Knowledge Base module directly.
 */
interface KnowledgeBaseSearchServiceInterface
{
    /**
     * @return list<array{article_id: string, title: string, score: float}>
     */
    public function search(string $query, int $limit = 5): array;
}
