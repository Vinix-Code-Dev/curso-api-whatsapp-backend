<?php

namespace App\Jobs;

use App\Libraries\Whatsapp;
use App\Models\Message;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payload;

    public $body;

    public $messageData;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payload, $body, $messageData = [])
    {
        $this->payload = $payload;
        $this->body = $body;
        $this->messageData = $messageData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $wp = new Whatsapp();
            $request = $wp->genericPayload($this->payload);

            $wam = new Message();
            $wam->body = $this->body;
            $wam->outgoing = true;
            $wam->type = 'template';
            $wam->wa_id = $request['contacts'][0]['wa_id'];
            $wam->wam_id = $request['messages'][0]['id'];
            $wam->status = 'sent';
            $wam->caption = '';
            $wam->data = serialize($this->messageData);
            $wam->save();
        } catch (Exception $e) {
            Log::error('Error sending message: '.$e->getMessage());
        }
    }
}
