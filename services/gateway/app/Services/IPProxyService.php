<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;

/**
 * Class IPProxyService
 *
 * Service layer for proxying IP management requests to the ip-management service.
 * Handles all HTTP methods for IP CRUD operations.
 *
 * This service encapsulates all IP proxy-related business rules and separates
 * them from HTTP concerns in the controller layer.
 */
class IPProxyService extends ProxyService
{
    /**
     * Proxy a request to the IP management service.
     *
     * Generic handler that proxies any HTTP method to the IP service.
     * Supports wildcard paths for nested resources.
     *
     * @param  string  $method  HTTP method (get, post, put, patch, delete)
     * @param  Request  $request  The original HTTP request
     * @param  string|null  $path  Optional path segment (e.g., '{id}', '{id}/history')
     */
    public function proxyRequest(string $method, Request $request, ?string $path = null): Response
    {
        $endpoint = $this->buildEndpoint($path);

        return $this->forwardToIPService($method, $endpoint, $request);
    }

    /**
     * Get all IP addresses with pagination.
     *
     * @param  Request  $request  The HTTP request with query parameters
     */
    public function getAllIPAddresses(Request $request): Response
    {
        return $this->forwardToIPService('get', $this->buildEndpoint(), $request);
    }

    /**
     * Create a new IP address.
     *
     * @param  Request  $request  The HTTP request with IP data
     */
    public function createIPAddress(Request $request): Response
    {
        return $this->forwardToIPService('post', $this->buildEndpoint(), $request);
    }

    /**
     * Get a specific IP address by ID.
     *
     * @param  string  $id  The IP address ID
     * @param  Request  $request  The HTTP request
     */
    public function getIPAddress(string $id, Request $request): Response
    {
        return $this->forwardToIPService('get', $this->buildEndpoint($id), $request);
    }

    /**
     * Update an IP address.
     *
     * @param  string  $id  The IP address ID
     * @param  Request  $request  The HTTP request with update data
     */
    public function updateIPAddress(string $id, Request $request): Response
    {
        return $this->forwardToIPService('put', $this->buildEndpoint($id), $request);
    }

    /**
     * Delete an IP address.
     *
     * @param  string  $id  The IP address ID
     * @param  Request  $request  The HTTP request
     */
    public function deleteIPAddress(string $id, Request $request): Response
    {
        return $this->forwardToIPService('delete', $this->buildEndpoint($id), $request);
    }

    /**
     * Get the change history for an IP address.
     *
     * @param  string  $id  The IP address ID
     * @param  Request  $request  The HTTP request
     */
    public function getIPAddressHistory(string $id, Request $request): Response
    {
        return $this->forwardToIPService('get', $this->buildEndpoint("{$id}/history"), $request);
    }

    /**
     * Get the audit log for an IP address.
     *
     * @param  string  $id  The IP address ID
     * @param  Request  $request  The HTTP request
     */
    public function getIPAddressAudit(string $id, Request $request): Response
    {
        return $this->forwardToIPService('get', $this->buildEndpoint("{$id}/audit"), $request);
    }

    /**
     * Build the endpoint path for the IP service.
     *
     * @param  string|null  $path  Optional path segment
     */
    protected function buildEndpoint(?string $path = null): string
    {
        $basePath = '/api/ip';

        if ($path) {
            // Ensure path doesn't start with a slash to avoid double slashes
            $path = ltrim($path, '/');

            return "{$basePath}/{$path}";
        }

        return $basePath;
    }
}
