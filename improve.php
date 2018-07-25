<?php

namespace src\App\Logger;
use src\App\Logger\Log;

trait LoggerTrait {


    use \Psr\Log\LoggerTrait;

    public function log($level, $message, array $context = array())
    {
        Log::log($level, $message, $context);
    }
}

namespace src\GenericProvider;

interface Provider {
    /**
     * @param Request $request
     * @return Response
     */
    public function get(Request $request) : Response;
}

interface Request extends \JsonSerializable {

}

interface Response extends \JsonSerializable {

}

namespace src\BestDataProvidingService;
use Psr\Cache\InvalidArgumentException;
use src\GenericProvider\Provider;

class DataProvider implements Provider
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     */
    public function __construct(string $host, string $user, string $password)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function get(Request $request): Response
    {
        // TODO: Implement get() method.
    }
}

use DateTime;
use Psr\Cache\CacheItemPoolInterface;
use src\App\Logger\LoggerTrait;
use src\GenericProvider\Request;
use src\GenericProvider\Response;

class CacheDataProviderDecorator implements Provider
{
    /**
     * @var Provider
     */
    private $provider;
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var string DateTime format
     */
    private $cacheTime;

    use LoggerTrait;

    public function __construct(Provider $provider, CacheItemPoolInterface $cache, $cacheTime = '+1 day')
    {
        $this->provider = $provider;
        $this->cache = $cache;
        $this->cacheTime = $cacheTime;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function get(Request $request): Response
    {
        //we need better way to get
        $cacheKey = $this->getCacheKey($request->jsonSerialize());
        try {
            $cacheItem = $this->cache->getItem($cacheKey);
        } catch (InvalidArgumentException $e) {
            $this->critical($e->getMessage(), ['exception' => $e]);
            return new Response();
        }


        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $result = $this->provider->get($request);

        $cacheItem
            ->set($result)
            ->expiresAt(
                (new DateTime())->modify($this->cacheTime)
            );

        return $result;

    }

    /**
     * @param string $input
     * @return string
     */
    public function getCacheKey(string $input)
    {
        return md5($input);
    }
}
