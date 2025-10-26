<?php

namespace Tests\Infrastructure;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestRabbitMQJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $message;
    public $processed = false;

    public function __construct($message = 'Test RabbitMQ Job')
    {
        $this->message = $message;
    }

    public function handle()
    {
        Log::info('TestRabbitMQJob processed', ['message' => $this->message]);
        $this->processed = true;
        
        // Store result in cache for testing purposes
        cache()->put('test_rabbitmq_job_' . md5($this->message), [
            'message' => $this->message,
            'processed_at' => now()->toISOString(),
            'processed' => true
        ], 60);
    }

    public function failed(\Throwable $exception)
    {
        Log::error('TestRabbitMQJob failed', [
            'message' => $this->message,
            'error' => $exception->getMessage()
        ]);
    }
}
