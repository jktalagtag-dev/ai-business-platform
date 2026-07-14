<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Contracts\Repositories\Ai\AiConversationRepositoryInterface;
use App\Application\Contracts\Repositories\Ai\AiMessageRepositoryInterface;
use App\Application\Contracts\Services\AI\AiProviderInterface;
use App\Application\Services\AI\AiToolRegistry;
use App\Application\Services\AI\ChatService;
use App\Application\Services\AI\ConversationService;
use App\Application\Services\AI\Tools\GetCurrentDateTimeTool;
use App\Application\Services\AI\Tools\GetTicketStatisticsTool;
use App\Application\Services\AI\Tools\SearchKnowledgeBaseTool;
use App\Domain\Ai\AiConversation;
use App\Infrastructure\AI\OpenAiCompatibleProvider;
use App\Infrastructure\Persistence\Eloquent\Repositories\Ai\AiConversationRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\Ai\AiMessageRepository;
use App\Policies\Ai\AiConversationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiConversationRepositoryInterface::class, AiConversationRepository::class);
        $this->app->bind(AiMessageRepositoryInterface::class, AiMessageRepository::class);

        $this->app->singleton(AiProviderInterface::class, fn ($app) => new OpenAiCompatibleProvider(
            baseUrl: (string) config('ai.base_url'),
            apiKey: (string) config('ai.api_key'),
            model: (string) config('ai.default_model'),
            embeddingModel: (string) config('ai.embedding_model'),
            timeout: (int) config('ai.request_timeout'),
        ));

        $this->app->singleton(AiToolRegistry::class, fn ($app) => new AiToolRegistry([
            $app->make(GetCurrentDateTimeTool::class),
            $app->make(GetTicketStatisticsTool::class),
            $app->make(SearchKnowledgeBaseTool::class),
        ]));

        $this->app->singleton(ConversationService::class, fn ($app) => new ConversationService(
            $app->make(AiConversationRepositoryInterface::class),
            (string) config('ai.default_model'),
        ));

        $this->app->singleton(ChatService::class, fn ($app) => new ChatService(
            $app->make(AiConversationRepositoryInterface::class),
            $app->make(AiMessageRepositoryInterface::class),
            $app->make(AiProviderInterface::class),
            $app->make(AiToolRegistry::class),
            (string) config('ai.default_system_prompt'),
            (int) config('ai.context_window_messages'),
            (int) config('ai.max_tool_iterations'),
        ));
    }

    public function boot(): void
    {
        Gate::policy(AiConversation::class, AiConversationPolicy::class);
    }
}
