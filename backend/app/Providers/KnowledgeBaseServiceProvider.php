<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Contracts\Repositories\KnowledgeBase\DocumentChunkRepositoryInterface;
use App\Application\Contracts\Repositories\KnowledgeBase\DocumentRepositoryInterface;
use App\Application\Contracts\Services\KnowledgeBase\PdfTextExtractorInterface;
use App\Domain\KnowledgeBase\Document;
use App\Infrastructure\KnowledgeBase\SmalotPdfTextExtractor;
use App\Infrastructure\Persistence\Eloquent\Repositories\KnowledgeBase\DocumentChunkRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\KnowledgeBase\DocumentRepository;
use App\Policies\KnowledgeBase\DocumentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class KnowledgeBaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DocumentRepositoryInterface::class, DocumentRepository::class);
        $this->app->bind(DocumentChunkRepositoryInterface::class, DocumentChunkRepository::class);
        $this->app->bind(PdfTextExtractorInterface::class, SmalotPdfTextExtractor::class);
    }

    public function boot(): void
    {
        Gate::policy(Document::class, DocumentPolicy::class);
    }
}
