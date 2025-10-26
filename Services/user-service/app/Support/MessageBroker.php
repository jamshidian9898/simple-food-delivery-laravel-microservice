<?php

namespace App\Support;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;

class MessageBroker
{
    /**
     * Dispatch a job with request context
     */
    public static function dispatch(string $jobClass, array $data = [], array $metadata = [])
    {
        $contextMetadata = RequestContext::getMessageMetadata($metadata);
        
        return Queue::push($jobClass, array_merge($data, [
            '_request_context' => $contextMetadata
        ]));
    }
    
    /**
     * Fire an event with request context
     */
    public static function event(string $eventClass, array $data = [], array $metadata = [])
    {
        $contextMetadata = RequestContext::getMessageMetadata($metadata);
        
        return Event::dispatch($eventClass, array_merge($data, [
            '_request_context' => $contextMetadata
        ]));
    }
    
    /**
     * Extract request context from message data
     */
    public static function extractContext(array $messageData): ?array
    {
        return $messageData['_request_context'] ?? null;
    }
    
    /**
     * Set request context from message data
     */
    public static function setContextFromMessage(array $messageData): void
    {
        $context = self::extractContext($messageData);
        
        if ($context && isset($context['request_id'])) {
            RequestContext::setRequestId($context['request_id']);
        }
    }
    
    /**
     * Publish message to external broker (RabbitMQ, Kafka, etc.)
     */
    public static function publish(string $topic, array $message, array $metadata = []): array
    {
        $contextMetadata = RequestContext::getMessageMetadata($metadata);
        
        return [
            'topic' => $topic,
            'message' => $message,
            'metadata' => $contextMetadata,
            'headers' => [
                'X-Request-ID' => $contextMetadata['request_id'],
                'Content-Type' => 'application/json',
            ]
        ];
    }
}
