<?php

namespace BuckhamDuffy\BdSupport\Commands;

use Illuminate\Mail\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SynapseSendHealthcheckEmailCommand extends Command
{
	protected $signature = 'synapse:send-healthcheck-email';
	protected $description = 'Command description';

	public function handle(): void
	{
		Mail::raw('Healthcheck Email', function(Message $message): void {
			$message->subject(url('/'));
			$message->to('healthchecks@synapse.run');
		});
	}
}
