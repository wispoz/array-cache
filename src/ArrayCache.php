<?php

namespace Wispoz\ArrayCache;

class ArrayCache implements \Psr\SimpleCache\CacheInterface
{
    private array $cache = [];
    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {

        $this->validateKey($key);
        if(isset($this->cache[$key]) && !$this->cache[$key]->isExpired(time())) {
            return $this->cache[$key]->getValue();
        }
        return $default;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $ttl = $this->normalizeTtl($ttl);
        $this->validateKey($key);
        $this->cache[$key]  = new Item($value,time()+$ttl);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        $this->validateKey($key);
        if(isset($this->cache[$key])){
            unset($this->cache[$key]);
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $this->cache = [];

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keys = $this->fromIterable($keys);
        $this->validateKeys($keys);
        $output = array_fill_keys($keys,$default);
        foreach ($keys as $key) {
            $key = (string) $key;
            $output[$key] = $this->get($key,$default);
        }
        return $output;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $values = $this->fromIterable($values);
        foreach ($values as $key=>$value) {
            $this->set($key,$value,$ttl);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $keys = $this->fromIterable($keys);
        $this->validateKeys($keys);
        $this->cache = array_filter($this->cache,static function($value,$key) use($keys) {
            $key = (string) $key;
            return !in_array($key,$keys,true);
        },ARRAY_FILTER_USE_BOTH);
        return  true;
    }
    private function normalizeTtl(null|int|string|\DateInterval $ttl): int
    {
        if($ttl ===null) {
            return  0;
        }

        if($ttl instanceof \DateInterval) {
            return (new \DateTime('@0'))->add($ttl)->getTimestamp();
        }
        return ((int) $ttl) > 0 ? $ttl : -1 ;
    }
    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {

        return isset($this->cache[$key]) && !$this->cache[$key]->isExpired(time());
    }
    private function fromIterable($iterable) :array
    {
        return $iterable instanceof  \Traversable ? iterator_to_array($iterable): (array) $iterable;
    }

    /**
     * @param string $key
     * @return void
     * @throws \Wispoz\ArrayCache\InvalidArgumentException
     */
    private function validateKey(string $key):void
    {
        if($key === '' || strpbrk($key,'/\@:{}[]')) {
            throw new \Wispoz\ArrayCache\InvalidArgumentException();
        }

    }

    /**
     * @param array $keys
     * @return void
     * @throws \Wispoz\ArrayCache\InvalidArgumentException
     */
    private function validateKeys(array $keys): void
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }
    }
}