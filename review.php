<?php

namespace src\Integration;

class DataProvider
{
    //типы ? стринги ?
    private $host;
    private $user;
    private $password;

    //типы ? стринги ?
    /**
     * @param $host
     * @param $user
     * @param $password
     */
    public function __construct($host, $user, $password)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @param array $request
     *
     * @return array
     */
    public function get(array $request)
    {
        // returns a response from external service
    }
}

// разные неймспейсы - лучше все держать в одной предметной области
namespace src\Decorator;

use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use src\Integration\DataProvider;

// 1 - Нейминг - лучше назвать DataProviderCacheDecorator
// 2 - Эт вообще не декоратор =))) это обычое наследование
class DecoratorManager extends DataProvider
{
    //нет phpDoc, idea ругается впоследвстии на $this->cache->getItem($cacheKey)
    //паблик 0_o
    public $cache;

    //не хватает phpDoc, idea ругается впоследвстии на $this->logger->critical('Error') + вообще можно обойтись без свойства
    //паблик 0_o
    public $logger;

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param CacheItemPoolInterface $cache
     */
    public function __construct($host, $user, $password, CacheItemPoolInterface $cache)
    {
        parent::__construct($host, $user, $password);
        $this->cache = $cache;
    }

    //лучше LoggerTrait
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    //нет такого метода, значит нечего и inheritdoc
    /**
     * {@inheritdoc}
     */
    public function getResponse(array $input)
    {
        try {
            $cacheKey = $this->getCacheKey($input);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $result = parent::get($input);

            //время кеша лучше выносить в конструктор параметром
            $cacheItem
                ->set($result)
                ->expiresAt(
                    (new DateTime())->modify('+1 day')
                );

            return $result;
        //мы \InvalidArgumentException ловим, а не все подряд
        //ну и конкретный вызов надо проверять а не весь код глушить
        } catch (Exception $e) {
            //эксепшен не надо в логе терять потом не восстановить цепочку
            $this->logger->critical('Error');
        }

        return [];
    }

    //паблик 0_o
    //нет phpDoc
    //json_encode может дать длинную строку - лучше md5
    //вообще лучше брать какой то ключ а не весь массив
    //если же нужен прям массив, то его надо отсортировать, ключи массива могут приходить в рандомном порядке, что даст разные кеш ключи
    public function getCacheKey(array $input)
    {
        return json_encode($input);
    }
}
