<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RequestContext
{
    private static ?string $requestId = null;
    private static array $context = [];
    
    /**
     * Set the request ID for the current context
     */
    public static function setRequestId(string $requestId): void
    {
        self::$requestId = $requestId;
        
        // Also set it in the request attributes if available
        if (request()) {
            request()->attributes->set('request_id', $requestId);
        }
        
        // Set logging context
        Log::withContext(['request_id' => $requestId]);
    }
    
    /**
     * Get the current request ID
     */
    public static function getRequestId(): ?string
    {
        // Try to get from static context first
        if (self::$requestId) {
            return self::$requestId;
        }
        
        // Try to get from request attributes
        if (request() && request()->attributes->has('request_id')) {
            return request()->attributes->get('request_id');
        }
        
        // Try to get from request headers
        if (request() && request()->hasHeader('X-Request-ID')) {
            return request()->header('X-Request-ID');
        }
        
        return null;
    }
    
    /**
     * Get or generate a request ID
     */
    public static function getOrGenerateRequestId(): string
    {
        $requestId = self::getRequestId();
        
        if (!$requestId) {
            $requestId = self::generateRequestId();
            self::setRequestId($requestId);
        }
        
        return $requestId;
    }
    
    /**
     * Generate a new request ID
     */
    public static function generateRequestId(): string
    {
        return Str::uuid()->toString();
    }
    
    /**
     * Clear the current context (useful for testing)
     */
    public static function clear(): void
    {
        self::$requestId = null;
        self::$context = [];
    }
    
    /**
     * Set additional context data
     */
    public static function setContext(string $key, mixed $value): void
    {
        self::$context[$key] = $value;
    }
    
    /**
     * Get context data
     */
    public static function getContext(?string $key = null): mixed
    {
        if ($key === null) {
            return self::$context;
        }
        
        return self::$context[$key] ?? null;
    }
    
    /**
     * Get headers for HTTP requests with request ID
     */
    public static function getHttpHeaders(array $additionalHeaders = []): array
    {
        $headers = [
            'X-Request-ID' => self::getOrGenerateRequestId(),
        ];
        
        return array_merge($headers, $additionalHeaders);
    }
    
    /**
     * Get metadata for message broker messages
     */
    public static function getMessageMetadata(array $additionalMetadata = []): array
    {
        $metadata = [
            'request_id' => self::getOrGenerateRequestId(),
            'service' => config('app.name'),
            'timestamp' => now()->toISOString(),
        ];
        
        return array_merge($metadata, $additionalMetadata);
    }
    
    /**
     * Create a child request ID (for sub-operations)
     */
    public static function createChildRequestId(?string $operation = null): string
    {
        $parentId = self::getOrGenerateRequestId();
        $childId = $parentId . '-' . Str::random(8);
        
        if ($operation) {
            $childId .= '-' . $operation;
        }
        
        return $childId;
    }
    
    /**
     * Log with request context
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $contextWithRequestId = array_merge([
            'request_id' => self::getOrGenerateRequestId(),
            'service' => config('app.name'),
        ], $context);
        
        Log::log($level, $message, $contextWithRequestId);
    }
    
    /**
     * Get all context for debugging
     */
    public static function debug(): array
    {
        return [
            'request_id' => self::getRequestId(),
            'static_request_id' => self::$requestId,
            'request_attributes' => request() ? request()->attributes->get('request_id') : null,
            'request_header' => request() ? request()->header('X-Request-ID') : null,
            'context' => self::$context,
        ];
    }
}
