<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Assistant — OpenAI-compatible provider
    |--------------------------------------------------------------------------
    |
    | base_url/api_key/model target the real OpenAI API by default, but any
    | endpoint implementing the same Chat Completions wire format (a
    | self-hosted gateway, Azure OpenAI, etc.) works by changing these three
    | values alone — no code changes.
    |
    */

    'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
    'api_key' => env('AI_API_KEY', env('OPENAI_API_KEY')),
    'default_model' => env('AI_MODEL', 'gpt-4o-mini'),
    'request_timeout' => (int) env('AI_REQUEST_TIMEOUT', 60),

    // Embeddings can be routed at a different endpoint than chat completions
    // (e.g. chat via OpenRouter, embeddings kept on real OpenAI, since
    // OpenRouter's embedding-model coverage is far narrower than its chat
    // coverage). Defaults fall back to the chat provider's own values, so a
    // single-provider setup (the default) is unaffected.
    'embedding_base_url' => env('AI_EMBEDDING_BASE_URL', env('AI_BASE_URL', 'https://api.openai.com/v1')),
    'embedding_api_key' => env('AI_EMBEDDING_API_KEY', env('AI_API_KEY', env('OPENAI_API_KEY'))),
    'embedding_model' => env('AI_EMBEDDING_MODEL', 'text-embedding-3-small'),

    // Optional OpenRouter attribution headers (HTTP-Referer / X-Title) —
    // purely cosmetic in OpenRouter's own dashboard/rankings, ignored by
    // plain OpenAI and other providers that don't recognize them.
    'site_url' => env('AI_SITE_URL'),
    'site_name' => env('AI_SITE_NAME'),

    'default_system_prompt' => env(
        'AI_DEFAULT_SYSTEM_PROMPT',
        'You are a helpful assistant embedded in an internal business platform. Be concise and accurate.'
    ),

    // How many of the most recent messages (regardless of role) are sent
    // to the model as conversation context on each turn. A simple,
    // documented v1 strategy — not true token-budget-based truncation.
    'context_window_messages' => (int) env('AI_CONTEXT_WINDOW_MESSAGES', 30),

    // Guards against a runaway tool-calling loop (the model requesting
    // tool after tool without ever producing a final reply).
    'max_tool_iterations' => (int) env('AI_MAX_TOOL_ITERATIONS', 5),

];
