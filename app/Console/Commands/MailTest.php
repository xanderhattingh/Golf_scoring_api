<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTest extends Command
{
    protected $signature = 'mail:test {to : Recipient email address}';

    protected $description = 'Send a test email to confirm the mail pipeline works';

    public function handle(): int
    {
        $to = $this->argument('to');
        $appName = config('app.name');
        $from = config('mail.from.address');

        $this->info("Sending test email to {$to} from {$from}…");

        try {
            Mail::raw(
                "Hi,\n\nThis is a test email from {$appName}.\n\nIf you're reading this in your inbox, the Brevo SMTP pipeline is working.\n\n— {$appName}",
                function ($message) use ($to, $appName) {
                    $message->to($to)->subject("{$appName} — mail test");
                }
            );

            $this->info('Sent. Check the inbox (and spam) — should land within a minute.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
