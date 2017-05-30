<?php

namespace Core;
use Exception;
use kernel;
use ReflectionMethod;
use Twig_Environment;
use Twig_Function;
use Twig_Loader_Filesystem;

/*! \addtogroup Core
 * @{
 */

/******************************************************************************/
/**
 * Controller class.
 */
class Controller extends Module
{
	protected $action  = null;
	protected $name    = null;
	protected $config  = null;
	protected $local   = null;
	protected $routes  = null;
	protected $session = null;
	protected $get     = null;
	protected $post    = null;
	protected $put     = null;
	protected $format  = null;
	protected $path    = null;
	protected $slugs   = null;

	public function __construct($name, $config, $path, $slugs)
	{
		parent::__construct();

		if (!isset($config[ROUTE_KEY_CONTROLLER]))
		{
			throw new Exception('Trying to create controller without name.');
		}
		if (!isset($config[ROUTE_KEY_ACTION]))
		{
			throw new Exception('Trying to create controller without action.');
		}
		if (!isset($config[ROUTE_KEY_METHOD]))
		{
			$config[ROUTE_KEY_METHOD] = false;
		}

		$this->action  = $config['action'];
		$this->name    = $name;
		$this->config  = $this->kernel->config;
		$this->routes  = $this->kernel->routes;
		$this->session = $this->kernel->session;
		$this->get     = $this->kernel->get;
		$this->post    = $this->kernel->post;
		$this->put     = $this->kernel->put;
		$this->format  = isset($config[ROUTE_KEY_FORMAT]) ? $config[ROUTE_KEY_FORMAT] : false;
		if ($this->kernel->format !== false)
		{
			if ($this->format === false)
			{
				$this->format = $this->kernel->format;
			}
			else if ($this->format != $this->kernel->format)
			{
				throw new Exception('Cannot fullfill request, invalid format specified');
			}
		}
		$this->path  = $path;
		$this->slugs = $slugs;

		$this->local = $this->kernel->loadRouteConfig($path);

		if (method_exists($this, 'init'))
		{
			$this->init();
		}
	}

	public function getName()
	{
		return $this->name;
	}

	public function method()
	{
		$methods = func_get_args();
		if (in_array($this->kernel->method, $methods))
		{
			return true;
		}
		return false;
	}

	public function setGet($params)
	{
		$this->get = $params;
	}

	public function getFormat()
	{
		return $this->format;
	}

	public function setFormat($value)
	{
		$this->format = $value;
	}

	/**
	 * Fetch POST/GET parameter.
	 *
	 * POST is searched first (is method was even POST), and GET is secondary.
	 *
	 * @return Value set in POST or GET. If not found, returns null.
	 */
	public function inputRaw()
	{
		$args  = func_get_args();
		$value = null;

		/* search POST */
		if ($this->method('post'))
		{
			$value = $this->post;
			foreach ($args as $arg)
			{
				if (isset($value[$arg]))
				{
					$value = $value[$arg];
				}
				else
				{
					/* value was not found, check GET */
					$value = null;
				}
			}
		}

		if ($value !== null)
		{
			/* value found from POST, return it */
			return $value;
		}

		/* search GET */
		$value = $this->get;
		foreach ($args as $arg)
		{
			if (isset($value[$arg]))
			{
				$value = $value[$arg];
			}
			else
			{
				/* value was not found, return null */
				return null;
			}
		}

		return $value;
	}

	public function inputRawDefault($default)
	{
		$method = new ReflectionMethod($this, 'inputRaw');
		$args   = func_get_args();
		array_shift($args);
		$value = $method->invokeArgs($this, $args);
		if ($value === null)
		{
			return $default;
		}
		return $value;
	}

	/**
	 * Fetch POST/GET parameter.
	 * String values are decoded (even post) using urldecode().
	 *
	 * POST is searched first (is method was even POST), and GET is secondary.
	 *
	 * @return Value set in POST or GET. If not found, returns null.
	 */
	public function input()
	{
		$method = new ReflectionMethod($this, 'inputRaw');
		$args   = func_get_args();
		$value  = $method->invokeArgs($this, $args);
		if ($value === null)
		{
			return null;
		}
		else if (!is_string($value))
		{
			return $value;
		}
		return urldecode($value);
	}

	public function inputDefault($default)
	{
		$method = new ReflectionMethod($this, 'inputRaw');
		$args   = func_get_args();
		array_shift($args);
		$value = $method->invokeArgs($this, $args);
		if ($value === null)
		{
			return $default;
		}
		else if (!is_string($value))
		{
			return $value;
		}
		return urldecode($value);
	}

	public function getDataDir($append = false)
	{
		$dir = $this->kernel->expand('{path:data}/' . $this->name);

		/* creata data directory for current route if needed */
		if (!file_exists($dir))
		{
			if (!@mkdir($dir, 0700, true))
			{
				throw new Exception('unable to create data directory for route ' . $this->name);
			}
		}

		/* check that this directory exists as a directory */
		if (is_dir($dir))
		{
			if ($append)
			{
				$dir .= '/' . $append;
			}
			if (!file_exists($dir))
			{
				if (!@mkdir($dir, 0700, true))
				{
					throw new Exception('unable to create data directory under route ' . $this->name);
				}
			}
			return $dir;
		}

		throw new Exception('data directory for route ' . $this->name . ' is invalid ' . $dir);
	}

	public function loadCustomConfig($config)
	{
		return $this->kernel->loadRouteCustomConfig($this->path, $config);
	}

	public function render($template = null, $params = array())
	{
		if ($this->format === 'json')
		{
			$ret = array(
				'success' => true,
				'data'    => $params,
			);
			return json_encode($ret);
		}
		else
		{
			if ($template === null)
			{
				/* just return true for empty content if template is null */
				return true;
			}
			/**
			 * @todo enable rendering of templates from another routes?
			 */
			// $parts = explode(':', $template, 2);
			// if (count($parts) == 2)
			// {

			// }
			$twig = $this->twigGet();
			return $twig->render($template, $params);
		}
		return false;
	}

	public function display($template = null, $params = array())
	{
		$view = $this->render($template, $params);
		if ($view === false)
		{
			return false;
		}
		if ($this->format === 'json')
		{
			header('Content-Type: application/json');
		}
		echo $view;
		return true;
	}

	public function renderRaw($content, $params = array())
	{
		if ($this->format === 'json')
		{
			$ret = array(
				'success' => true,
				'data'    => $params,
				'raw'     => $content,
			);
			return json_encode($ret);
		}
		else
		{
			return $content;
		}
		return false;
	}

	public function renderAction()
	{
		$actionMethodName = $this->action . CONTROLLER_ACTION_EXTENSION;
		if (!method_exists($this, $actionMethodName))
		{
			throw new Exception('invalid action ' . $this->action);
		}

		$method = new ReflectionMethod($this, $actionMethodName);
		$args   = array();
		foreach ($method->getParameters() as $parameter)
		{
			$name = $parameter->getName();
			if (!isset($this->slugs[$name]))
			{
				if (!$parameter->isOptional())
				{
					throw new Exception('action ' . $this->action . ' wants parameter named ' . $name);
				}
			}
			else
			{
				$args[] = $this->slugs[$name]['value'];
			}
		}

		return $method->invokeArgs($this, $args);
	}

	public function renderRoute($route, $slugs = array(), $get = false)
	{
		$slugs_converted = array();
		foreach ($slugs as $name => $slug)
		{
			$slugs_converted[$name] = array('slug' => $name, 'value' => $slug);
		}

		if (strpos($route, ':') === false)
		{
			$route = $this->name . ':' . $route;
		}

		$controller = $this->kernel->route($route, false, $get, $slugs_converted);

		return $this->kernel->render($controller);
	}

	public function renderErrorException($e)
	{
		if ($this->format === 'json')
		{
			$ret = array(
				'success' => false,
				'msg'     => $e->getMessage(),
				'code'    => $e->getCode(),
				'file'    => $e->getFile(),
				'line'    => $e->getLine(),
			);
			return json_encode($ret);
		}
		else
		{
			$params['msg']  = $e->getMessage();
			$params['code'] = $e->getCode();
			$params['file'] = $e->getFile();
			$params['line'] = $e->getLine();
			return $this->twigGet()->render('error-exception.html', $params);
		}
		return false;
	}

	public function twigGet($extra_paths = false)
	{
		$templates = array();
		if ($this->path)
		{
			$templates[] = $this->path . '/views';
		}
		$templates[] = $this->kernel->expand('{path:views}');
		$common      = $this->kernel->expand('{path:routes}') . '/common/views';
		if (is_dir($common))
		{
			$templates[] = $common;
		}
		$templates[] = $this->kernel->expand('{path:routes}');

		if (is_array($extra_paths))
		{
			$templates = array_merge($templates, $extra_paths);
		}

		$twig_loader = new Twig_Loader_Filesystem($templates);
		$config      = $this->kernel->getConfigValue('twig');
		if (!$config)
		{
			$config = array('cache' => false);
		}
		if (isset($config['cache']) && $config['cache'] !== false)
		{
			/* expand twig cache path */
			$config['cache'] = $this->kernel->expand($config['cache']);
		}
		$twig = new Twig_Environment($twig_loader, $config);

		/* add all custom functions to twig here */
		//$twig->addExtension($this);
		$twig->addFunction(new Twig_Function('route', array($this, 'route')));
		$twig->addFunction(new Twig_Function('tr', array($this, 'tr')));
		$twig->addFunction(new Twig_Function('trJs', array($this, 'trJs'), array('is_safe' => array('all'))));

		$twig->addFunction(new Twig_Function('path', array($this, 'linkPath')));

		/* assets */
		$twig->addFunction(new Twig_Function('asset', array($this, 'linkAsset')));
		$twig->addFunction(new Twig_Function('image', array($this, 'linkImage')));
		$twig->addFunction(new Twig_Function('css', array($this, 'linkCss')));
		$twig->addFunction(new Twig_Function('js', array($this, 'linkJavascript')));
		$twig->addFunction(new Twig_Function('javascript', array($this, 'linkJavascript')));

		$twig->addFunction(new Twig_Function('render', array($this, 'renderRoute')));
		$twig->addFunction(new Twig_Function('username', array($this, 'username')));
		$twig->addFunction(new Twig_Function('name', array($this, 'twigName')));
		$twig->addFunction(new Twig_Function('authorize', array($this, 'authorize')));

		$twig->addFunction(new Twig_Function('lang', array($this, 'lang')));
		$twig->addFunction(new Twig_Function('msg', array($this->kernel, 'msgGet')));
		$twig->addFunction(new Twig_Function('config', array($this->kernel, 'getConfigValue')));
		$twig->addFunction(new Twig_Function('expand', array($this->kernel, 'expand')));

		$twig->addFunction(new Twig_Function('bytes_to_human', array($this, 'twigBytesToHuman')));

		return $twig;
	}

	public function route($route, $slugs = array(), $get = array(), $options = array())
	{
		$slugs_converted = array();
		foreach ($slugs as $name => $slug)
		{
			$slugs_converted[$name] = array('slug' => $name, 'value' => $slug);
		}

		if (strpos($route, ':') === false)
		{
			$route = $this->name . ':' . $route;
		}

		return $this->kernel->route($route, true, $get, $slugs_converted, $options);
	}

	public function routePath($route)
	{
		if (strpos($route, ':') === false)
		{
			$route = $this->name . ':' . $route;
		}
		return $this->kernel->routePath($route);
	}

	public function tr($text)
	{
		$method = new ReflectionMethod('kernel', 'tr');
		$args   = func_get_args();
		return $method->invokeArgs($this->kernel, $args);
	}

	public function trJs($text)
	{
		$text = htmlentities($this->tr($text));

		/* if there are more parameters, try to print them into the text */
		if (func_num_args() > 1)
		{
			$vars = func_get_args();
			array_shift($vars);
			foreach ($vars as &$var)
			{
				$var = '"+' . $var . '+"';
			}
			$text = vsprintf($text, $vars);
		}

		return $text;
	}

	public function linkPath($path)
	{
		return $this->kernel->url($path);
	}

	public function linkAsset($asset, $route = false, $postdir = false)
	{
		$list = explode(':', $asset, 2);
		if (count($list) == 2)
		{
			$route = $list[0];
			$asset = $list[1];
		}
		return $this->kernel->url(($route ? '/' . $route : '') . ($postdir ? '/' . $postdir : '') . '/' . $asset);
	}

	public function pathAsset($asset, $route, $postdir, &$webdir = null)
	{
		$list = explode(':', $asset, 2);
		if (count($list) == 2)
		{
			$route = $list[0];
			$asset = $list[1];
		}
		$path = false;
		if ($route)
		{
			$webdir = $route . ($postdir ? '/' . $postdir : '') . '/' . $asset;
			$path   = $this->kernel->expand('{path:routes}') . '/' . $route . '/public' . ($postdir ? '/' . $postdir : '') . '/' . $asset;
		}
		else
		{
			$webdir = ($postdir ? $postdir . '/' : '') . $asset;
			$path   = $this->kernel->expand('{path:web}') . '/' . $webdir;
		}
		if (!file_exists($path))
		{
			$this->kernel->log(LOG_ERR, 'Invalid asset, file not found: ' . $path);
		}
		return $path;
	}

	public function linkImage($image, $global = false)
	{
		return $this->linkAsset($image, ($global ? false : $this->name), 'images');
	}

	public function linkCss($css = false, $global = false)
	{
		/* return single url */
		if ($css !== false)
		{
			return $this->linkAsset($css, ($global ? false : $this->name), 'css');
		}

		/*
		 * return list of css files defined in configuration
		 */

		/* if none defined */
		if (!isset($this->config['css']))
		{
			return array();
		}

		$values = array();
		$this->expandConfigAssets($this->config, 'css', 'css', $values, false);

		/* if debug is false and packed version is set, return it */
		if ($this->kernel->debug() == false)
		{
			$value = $this->kernel->getConfigValue('setup', 'cache', 'css');
			if ($value)
			{
				/* remove static files */
				foreach ($values as $k => $v)
				{
					if ($v['type'] == 'static')
					{
						unset($values[$k]);
					}
				}
				/* append compressed version of static files */
				array_unshift($values, array(
					'type' => 'static',
					'path' => null,
					'url'  => $this->kernel->url($value),
				));
			}
		}

		return $values;
	}

	public function linkJavascript($js = false, $global = false)
	{
		/* return single url */
		if ($js !== false)
		{
			return $this->linkAsset($js, ($global ? false : $this->name), 'js');
		}

		/*
		 * return list of javascript files defined in configuration
		 */

		/* if none defined */
		if (!isset($this->config['javascript']))
		{
			return array();
		}

		$values = array();
		$this->expandConfigAssets($this->config, 'javascript', 'js', $values, false);

		/* if debug is false and packed version is set, return it */
		if ($this->kernel->debug() == false)
		{
			$value = $this->kernel->getConfigValue('setup', 'cache', 'javascript');
			if ($value)
			{
				/* remove static files */
				foreach ($values as $k => $v)
				{
					if ($v['type'] == 'static')
					{
						unset($values[$k]);
					}
				}
				/* append compressed version of static files */
				array_unshift($values, array(
					'type' => 'static',
					'path' => null,
					'url'  => $this->kernel->url($value),
				));
			}
		}

		return $values;
	}

	public function expandConfigAssets($config, $key, $postdir, &$values, $route)
	{
		foreach ($config[$key] as $value)
		{
			if (is_string($value) && (strpos($value, 'http://') === 0 || strpos($value, 'https://') === 0))
			{
				$values[] = array(
					'type'     => 'url',
					'path'     => null,
					'url'      => $value,
					'route'    => null,
					'relative' => null,
				);
			}
			else if (!isset($value['twig']) && substr($value, -1) == '*')
			{
				/* wildcard append from route config */
				list($subroute) = explode(':', $value, 2);
				$routepath      = $this->kernel->expand('{path:routes}/' . $subroute);
				$subconfig      = $this->kernel->loadRouteConfig($routepath);
				if (isset($subconfig[$key]))
				{
					$this->expandConfigAssets($subconfig, $key, $postdir, $values, $subroute);
				}
			}
			else
			{
				/* single one */
				$type = 'static';
				if (isset($value['twig']))
				{
					$type  = 'twig';
					$value = $value['twig'];
				}

				$path = $this->pathAsset($value, $route, $postdir, $webdir);
				$url  = $this->linkAsset($value, $route, $postdir);

				$realroute = $route;
				$list      = explode(':', $value, 2);
				if (count($list) == 2)
				{
					$realroute = $list[0];
				}

				$values[] = array(
					'type'  => $type,
					'path'  => $path,
					'url'   => $url,
					'route' => $realroute,
					'web'   => $webdir,
				);
			}
		}
	}

	public function username()
	{
		return $this->session->get('username');
	}

	public function twigName()
	{
		$user = $this->session->getUser();
		$name = null;
		if ($user)
		{
			$name = $user->get('name');
		}
		if (empty($name))
		{
			$name = $this->session->get('username');
		}
		return $name;
	}

	public function authorize($access, $user = null)
	{
		return $this->session->authorize($access, $user);
	}

	public function lang()
	{
		return $this->kernel->lang;
	}

	public function twigBytesToHuman($bytes, $decimals = 2, $divider = 1024)
	{
		return Compose::bytesToHuman($bytes, $decimals, $divider);
	}
}

/*! @} endgroup Core */
