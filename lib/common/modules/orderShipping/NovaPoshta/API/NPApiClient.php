<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);


namespace common\modules\orderShipping\NovaPoshta\API;

use common\modules\orderShipping\NovaPoshta\API\EndPoints\Address;
use common\modules\orderShipping\NovaPoshta\API\EndPoints\Counterparty;
use common\modules\orderShipping\NovaPoshta\API\EndPoints\Common;
use common\modules\orderShipping\NovaPoshta\API\EndPoints\Document;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class NPApiClient
{
    /**
     * HTTP status codes
     */
    const HTTP_NO_CONTENT = 204;

    /** @var Client */
    private $client;

    /** @var array */
    public $config =[
        'url' => 'https://api.novaposhta.ua/v2.0/json/',
    ];
    /** @var array */
    private $options =[
        'headers' => [
            'Accept' => 'application/json',
        ],
        'json' => [
            'apiKey' => '',
            'language' => '',
        ],
    ];

    public function __construct(
        Client $client
    )
    {
        $this->client = $client;
    }

    public function withApiKey(string $apiKey): self
    {
        $this->options['json']['apiKey'] = $apiKey;
        return $this;
    }

    public function withLanguage(string $language): self
    {
        $this->options['json']['language'] = $language;
        return $this;
    }

    public function get(string $url, array $options = [], array $headers = [])
    {
        $response = $this->client->get($this->config['url'] . $url, $this->mergeOptions($options, $headers));
        return $this->parseResponseBody($response);
    }

    public function post(string $url, array $options = [], array $headers = [])
    {
        $response = $this->client->post($this->config['url'] . $url, $this->mergeOptions($options, $headers));
        return $this->parseResponseBody($response);
    }

    public function buildQueryString(array $params = [], array $filters = [], array $expand = [])
    {
        $result = [];
        if (count($params) > 0 ) {
            foreach ($params as $key => $value) {
                $result[$key] = $value;
                if ($value === true) {
                    $result[$key] = 'true';
                }
                if ($value === false) {
                    $result[$key] = 'false';
                }
            }
        }
        if (count($filters) > 0) {
            $result['filter'] = $filters;
        }
        if (count($expand) > 0) {
            $result['expand'] = rtrim(implode(',', $expand), ',');
        }
        return '?' . http_build_query($result, '', '&');
    }

    public function createJsonBody(array $parameters)
    {
        return (count($parameters) === 0) ? null : json_encode($parameters, empty($parameters) ? JSON_FORCE_OBJECT : 0);
    }

    public function getLanguage(): string
    {
        return $this->options['json']['language'];
    }

    public function getApiKey(): string
    {
        return $this->options['json']['apiKey'];
    }

    public function api($name)
    {
        switch ($name) {
            case 'common':
                $api = new Common($this);
                break;
            case 'address':
                $api = new Address($this);
                break;
            case 'counterparty':
                $api = new Counterparty($this);
                break;
            case 'document':
                $api = new Document($this);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Undefined api instance called: "%s"', $name));
        }
        return $api;
    }

    private function mergeOptions(array $options = [], array $headers = [])
    {
        $headers = [
            'headers' => $headers,
        ];
        return array_replace_recursive($this->options, $options, $headers);
    }

    /**
     * @param ResponseInterface $response
     * @return mixed|null
     */
    private function parseResponseBody(ResponseInterface $response)
    {
        $body = (string) $response->getBody();
        if (empty($body)) {
            if ($response->getStatusCode() === self::HTTP_NO_CONTENT) {
                return null;
            }
            throw new \DomainException('No response body found.');
        }
        $object = @json_decode($body, false);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \DomainException("Unable to decode response: '{$body}'.");
        }
        return $object;
    }

    /**
     * @param string|UriInterface $uri
     * @param array $options
     * @return ResponseInterface
     */
    public function getOriginal($uri, array $options = [])
    {
        return $this->client->get($uri, $options);
    }
}
