<?php

declare(strict_types=1);

namespace App;

use OpenApi\Attributes as OAT;

#[OAT\Info(
    title: 'AI Business Platform API',
    version: '1.0.0',
    description: 'Authentication & RBAC endpoints for the AI Business Platform. See ARCHITECTURE.md, API.md, and BACKEND.md in the repository root for the full system design.'
)]
#[OAT\Server(url: '/', description: 'Current server')]
#[OAT\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum personal access token',
    description: "Send the token returned by /api/v1/auth/register or /api/v1/auth/login as 'Authorization: Bearer {token}'."
)]
final class OpenApi
{
    // Annotation host only — never instantiated.
}
