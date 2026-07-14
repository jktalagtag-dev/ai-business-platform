<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Knowledge Base — chunking & retrieval
    |--------------------------------------------------------------------------
    |
    | chunk_size/chunk_overlap are character counts (not tokens) for the
    | simple fixed-size sliding-window chunker (Domain\KnowledgeBase\TextChunker)
    | — chunking never crosses a page boundary, so citations can always
    | name a page number.
    |
    */

    'chunk_size' => (int) env('KB_CHUNK_SIZE', 1000),
    'chunk_overlap' => (int) env('KB_CHUNK_OVERLAP', 150),

    // Top-K chunks returned by a single retrieval/ask call.
    'top_k' => (int) env('KB_TOP_K', 5),

    // Enforced by UploadKnowledgeBaseDocumentRequest.
    'max_upload_size_kb' => (int) env('KB_MAX_UPLOAD_SIZE_KB', 20480),

];
