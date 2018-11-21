<?php

namespace Core;

use kernel;
use ReflectionMethod;

/*! \addtogroup Core
 * @{
 */

/******************************************************************************/
/**
 * Controller class.
 */
abstract class Module
{
    protected $kernel = null;
    private $errors   = array();
    private $cache    = null;

    public function __construct()
    {
        $this->kernel = kernel::getInstance();
    }

    /**
     * Add pre and post call hooks here, if defined.
     */
    // public function __call($method, $args)
    // {
    //  $this->kernel->log(LOG_DEBUG, "$method called with " . count($args));
    //  // $method = new ReflectionMethod($this, 'input');
    //  // $args   = func_get_args();
    //  // array_shift($args);
    //  // $value = $method->invokeArgs($this, $args);
    // }

    public static function getModuleValue()
    {
        $argv = func_get_args();
        array_unshift($argv, get_called_class());
        $reflection = new ReflectionMethod('kernel', 'getModuleValue');
        /* return value from current module configuration */
        return $reflection->invokeArgs(kernel::getInstance(), $argv);
    }

    public static function getConfigValue()
    {
        $argv       = func_get_args();
        $reflection = new ReflectionMethod('kernel', 'getConfigValue');
        /* return value from configuration */
        return $reflection->invokeArgs(kernel::getInstance(), $argv);
    }

    public function setError($error)
    {
        $this->errors[] = $error;
    }

    public function getError()
    {
        $n = count($this->errors);
        if ($n < 1) {
            return false;
        }
        return $this->errors[$n - 1];
    }

    public function logError($message)
    {
        /* add error to current module log */
        $this->setError($message);

        /* log error through kernel */
        $this->kernel->log(LOG_ERR, $message, get_class($this));

        return $message;
    }

    /**
     * Emit a signal.
     */
    public function emit($name)
    {
        $args = func_get_args();
        array_unshift($args, get_called_class());
        $method = new ReflectionMethod($this->kernel, 'emit');
        $method->invokeArgs($this->kernel, $args);
    }

    /**
     * Wrapper method for ease of access to cache.
     * Also the key will be prefixed using current class (module) name
     * ("<class_name>::$key").
     */
    public function cacheGet($key, $default = null)
    {
        $cache = \kernel::getCacheInstance();
        if (!$cache) {
            return null;
        }
        /* prefix cache key with current class(module) name */
        $key  = get_class($this) . '-' . $key;
        $key  = preg_replace('@[\{}\()/\@:\\\\]@', '-', $key);
        $item = $cache->getItem($key);
        if ($item->isHit()) {
            return $item->get();
        }
        return $default;
    }

    /**
     * Wrapper method for ease of access to cache.
     * Also the key will be prefixed using current class (module) name
     * ("<class_name>::$key").
     */
    public function cacheSet($key, $value, $ttl = 600)
    {
        $cache = \kernel::getCacheInstance();
        if (!$cache) {
            return null;
        }
        /* prefix cache key with current class(module) name */
        $key  = get_class($this) . '-' . $key;
        $key  = preg_replace('@[\{}\()/\@:\\\\]@', '-', $key);
        $item = $cache->getItem($key);
        $item->set($value)->expiresAfter($ttl);
        $cache->save($item);
        return true;
    }

    /**
     * Wrapper method for ease of access to cache.
     * Also the key will be prefixed using current class (module) name
     * ("<class_name>::$key").
     */
    public function cacheDelete($key)
    {
        if (!$this->cache) {
            $this->cache = $this->kernel->getCacheInstance();
            if (!$this->cache) {
                return null;
            }
        }
        /* prefix cache key with current class(module) name */
        $key = get_class($this) . '::' . $key;
        $this->cache->delete($key);
        return true;
    }
}

/*! @} endgroup Core */
