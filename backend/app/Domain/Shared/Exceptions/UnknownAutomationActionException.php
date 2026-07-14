<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

/**
 * A workflow step references an action name no longer registered in
 * ActionRegistry — normally prevented at workflow-creation time by
 * validation, so reaching this at execution time means an action was
 * removed after workflows referencing it were already created. Treated as
 * a job failure (triggers the normal retry/eventual-failure path) rather
 * than silently skipped, since it signals a real configuration problem.
 */
final class UnknownAutomationActionException extends DomainException {}
