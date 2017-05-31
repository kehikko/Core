<?php

namespace Core;
use kernel;
use ReflectionMethod;
use ReflectionFunction;

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
	// 	$this->kernel->log(LOG_DEBUG, "$method called with " . count($args));
	// 	// $method = new ReflectionMethod($this, 'input');
	// 	// $args   = func_get_args();
	// 	// array_shift($args);
	// 	// $value = $method->invokeArgs($this, $args);
	// }

	public function getModuleValue()
	{
		$argv = func_get_args();
		array_unshift($argv, get_class($this));
		$reflection = new ReflectionMethod('kernel', 'getModuleValue');
		/* return value from current module configuration */
		return $reflection->invokeArgs($this->kernel, $argv);
	}

	public function getConfigValue()
	{
		$argv = func_get_args();
		$reflection = new ReflectionMethod('kernel', 'getConfigValue');
		/* return value from configuration */
		return $reflection->invokeArgs($this->kernel, $argv);
	}

	public function setError($error)
	{
		$this->errors[] = $error;
	}

	public function getError()
	{
		$n = count($this->errors);
		if ($n < 1)
		{
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
		$signal = get_called_class() . ':' . $name;
		$args   = func_get_args();
		array_shift($args);
		array_unshift($args, $signal);
		
		$this->kernel->log(LOG_DEBUG, 'Emit signal, name: ' . $signal . ', number of arguments: ' . count($args));

		$calls = $this->kernel->getConfigValue('setup', 'signals', $signal);
		if (is_array($calls))
		{
			foreach ($calls as $call)
			{
				if (isset($call['class']) && isset($call['method']))
				{
					$className  = $call['class'];
					$methodName = $call['method'];
					$this->kernel->log(LOG_DEBUG, ' - sending signal: ' . $className . '::' . $methodName);
					$method = new ReflectionMethod($className, $methodName);
					$method->invokeArgs(null, $args);
				}
				else if (isset($call['function']))
				{
					$functionName = $call['function'];
					$this->kernel->log(LOG_DEBUG, ' - sending signal: ' . $functionName);
					$function = new ReflectionFunction($functionName);
					$function->invokeArgs($args);
				}
			}
		}
	}

	/**
	 * Wrapper method for ease of access to cache.
	 * Also the key will be prefixed using current class (module) name
	 * ("<class_name>::$key").
	 */
	public function cacheGet($key, $default = null)
	{
		if (!$this->cache)
		{
			$this->cache = $this->kernel->getCacheInstance();
			if (!$this->cache)
			{
				return null;
			}
		}
		/* prefix cache key with current class(module) name */
		$key   = get_class($this) . '::' . $key;
		$value = $this->cache->get($key);
		if ($value === null && $default !== null)
		{
			return $default;
		}
		return $value;
	}

	/**
	 * Wrapper method for ease of access to cache.
	 * Also the key will be prefixed using current class (module) name
	 * ("<class_name>::$key").
	 */
	public function cacheSet($key, $value, $ttl = 600)
	{
		if (!$this->cache)
		{
			$this->cache = $this->kernel->getCacheInstance();
			if (!$this->cache)
			{
				return null;
			}
		}
		/* prefix cache key with current class(module) name */
		$key = get_class($this) . '::' . $key;
		$this->cache->set($key, $value, $ttl);
		return true;
	}

	/**
	 * Wrapper method for ease of access to cache.
	 * Also the key will be prefixed using current class (module) name
	 * ("<class_name>::$key").
	 */
	public function cacheDelete($key)
	{
		if (!$this->cache)
		{
			$this->cache = $this->kernel->getCacheInstance();
			if (!$this->cache)
			{
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
