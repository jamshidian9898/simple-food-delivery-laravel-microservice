<?php

namespace App\Http;

use App\Support\RequestContext;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

class HttpClient
{
    /**
     * Create an HTTP client with request ID propagation
     */
    public static function withRequestId(array $additionalHeaders = []): PendingRequest
    {
        $headers = RequestContext::getHttpHeaders($additionalHeaders);
        
        return Http::withHeaders($headers);
    }
    
    /**
     * Make a GET request with request ID propagation
     */
    public static function get(string $url, array $query = [], array $headers = [])
    {
        return self::withRequestId($headers)->get($url, $query);
    }
    
    /**
     * Make a POST request with request ID propagation
     */
    public static function post(string $url, array $data = [], array $headers = [])
    {
        return self::withRequestId($headers)->post($url, $data);
    }
    
    /**
     * Make a PUT request with request ID propagation
     */
    public static function put(string $url, array $data = [], array $headers = [])
    {
        return self::withRequestId($headers)->put($url, $data);
    }
    
    /**
     * Make a DELETE request with request ID propagation
     */
    public static function delete(string $url, array $data = [], array $headers = [])
    {
        return self::withRequestId($headers)->delete($url, $data);
    }
}
