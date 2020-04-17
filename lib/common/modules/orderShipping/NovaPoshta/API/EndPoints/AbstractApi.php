<?php
declare (strict_types=1);

namespace common\modules\orderShipping\NovaPoshta\API\EndPoints;

use common\modules\orderShipping\NovaPoshta\API\NPApiClient;

abstract class AbstractApi
{
    /** @var NPApiClient */
    protected $client;
    /** @var \yii\caching\CacheInterface */
    protected $cache;

    public function __construct(
        NPApiClient $client
    )
    {
        $this->cache = \Yii::$app->getCache();
        $this->client = $client;
    }

    /**
     * @param string $method
     * @param array|null $properties
     * @param string|null $model
     * @return array
     */
    protected function getOptions(string $method = '', ?array $properties = null, ?string $model = null)
    {
        $options = $this->options;
        $options['json']['calledMethod'] = $method;
        $options['json']['methodProperties'] = $properties ?? $options['json']['methodProperties'];
        $options['json']['modelName'] = $model ?? $options['json']['modelName'];
        return $options;
    }

    /**
     * @param object $response
     * @param string $defaultMessage
     */
    protected function throwError($response, string $defaultMessage = 'Api Nova Poshta cannot receive data')
    {
        if ($response->success === false &&
            is_array($response->errors) &&
            count($response->errors)
        ) {
            throw new \RuntimeException("Api Nova Poshta Error: {$response->errors[0]}");
        }
        throw new \RuntimeException($defaultMessage);
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function getFromCache(string $key)
    {
        try {
            return $this->cache->get($key);
        } catch (\Exception $e) {
            \Yii::error($e->getMessage());
        }
        return false;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $duration
     * @return bool
     */
    protected function setToCache(string $key , $value, int $duration = 86400)
    {
        try {
            return $this->cache->set($key, $value, $duration);
        } catch (\Exception $e) {
            \Yii::error($e->getMessage());
        }
        return false;
    }
}
