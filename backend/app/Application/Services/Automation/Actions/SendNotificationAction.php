<?php

declare(strict_types=1);

namespace App\Application\Services\Automation\Actions;

use App\Application\Contracts\Services\Automation\AutomationActionInterface;
use App\Infrastructure\Notifications\Automation\WorkflowNotification;
use Illuminate\Support\Facades\Notification;

/**
 * config: {to: string (email address), subject: string, message: string}
 * — subject/message support {{placeholder}} substitution (already resolved
 * by the time execute() is called; see PlaceholderResolver). `to` is a
 * literal address rather than resolved from a User/Employee record — the
 * workflow author configures the recipient directly, since none of the
 * events this engine reacts to carry a resolvable "acting user" to notify.
 */
final class SendNotificationAction implements AutomationActionInterface
{
    public function name(): string
    {
        return 'send_notification';
    }

    public function execute(array $config, array $context): array
    {
        $to = (string) $config['to'];

        Notification::route('mail', $to)->notify(new WorkflowNotification(
            (string) $config['subject'],
            (string) $config['message'],
        ));

        return ['sent_to' => $to];
    }
}
