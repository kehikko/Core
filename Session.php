<?php

namespace Core;
use kernel;

/*! \addtogroup Core
 * @{
 */

class Session extends AbstractModule
{
	private $user = null;

	public function __construct()
	{
		parent::__construct();
		session_start();

		/* do authentication using basic auth
		 * if basic auth variables are set and
		 * session is not logged in.
		 */
		$username = $this->get('username');
		if (!$username && isset($_SERVER['PHP_AUTH_USER']))
		{
			$username = $_SERVER['PHP_AUTH_USER'];
			$password = $_SERVER['PHP_AUTH_PW'];
			$this->authenticate($username, $password);
		}
	}

	public function destroy()
	{
		$username = $this->get('username');
		session_destroy();
		$this->user = null;
		$this->kernel->log(LOG_DEBUG, 'Logout, username: ' . $username, 'session');
	}

	public function authenticate($username, $password)
	{
		$user = null;

		/* loop through authenticator options to find requested user */
		$class = null;
		foreach ($this->getModuleValue('authenticators') as $class)
		{
			try
			{
				$user    = new $class($username, $password);
				$nologin = $this->kernel->getConfigValue('setup', 'no-login');
				if (is_array($nologin) && $this->authorize($nologin, $user) && !$this->authorize('role:admin', $user))
				{
					$this->kernel->log(LOG_INFO, 'Login attempt with account that is not allowed to login, authenticator class: ' . $class . ', username: ' . $username, 'session');
					$user = null;
					continue;
				}
				$this->kernel->log(LOG_DEBUG, 'Successfull login, authenticator class: ' . $class . ', username: ' . $username, 'session');
			}
			catch (Exception403 $e)
			{
				$user = null;
				continue;
			}
			break;
		}

		/* if authenctication was successfull */
		if ($user)
		{
			$this->set('username', $user->get('username'));
			$this->user = $user;
			/* emit auth success */
			$this->emit(__FUNCTION__, $class, $username, true);
			return true;
		}

		$this->kernel->log(LOG_NOTICE, 'Authentication failure, username: ' . $username, 'session');
		$this->set('username', false);
		$this->user = null;

		/* emit auth failure */
		$this->emit(__FUNCTION__, $class, $username, false);

		return false;
	}

	public function set($name, $value)
	{
		$_SESSION[$name] = $value;
	}

	public function fakeUser($username)
	{
		if ($this->authorize('role:root'))
		{
			$user = null;
			foreach ($this->getModuleValue('authenticators') as $class)
			{
				try {
					$user = new $class($username);
				}
				catch (Exception403 $e)
				{
					$user = null;
					continue;
				}
				break;
			}
			if ($user)
			{
				$this->kernel->log(LOG_NOTICE, 'Fake from user ' . $this->get('username') . ' to user ' . $username . ' successfull.', 'session');
				$this->set('username', $user->get('username'));
				$this->user = $user;
				return true;
			}
		}

		return false;
	}

	public function get($name)
	{
		if (isset($_SESSION[$name]))
		{
			return $_SESSION[$name];
		}
		if ($name == 'username')
		{
			return false;
		}
		return null;
	}

	public function getUser($_username = null)
	{
		if ($this->user && $_username === null)
		{
			return $this->user;
		}

		$username = null;
		if ($_username !== null)
		{
			$username = $_username;
		}
		else
		{
			$username = $this->get('username');
		}
		if (!$username)
		{
			return null;
		}

		/* loop through authenticator options to find requested user */
		$user = null;
		foreach ($this->getModuleValue('authenticators') as $class)
		{
			try {
				$user = new $class($username);
			}
			catch (Exception403 $e)
			{
				$user = null;
				continue;
			}
			break;
		}

		if ($user && $_username !== null)
		{
			return $user;
		}
		else if ($_username !== null)
		{
			return null;
		}

		$this->user = $user;
		if (!$this->user)
		{
			$this->destroy();
			throw new Exception403('User not found.');
		}

		return $this->user;
	}

	public function authorize($access, $user = null)
	{
		if (is_string($access))
		{
			return $this->authorizeSingle($access, $user);
		}
		if (is_array($access))
		{
			foreach ($access as $single)
			{
				$r = $this->authorizeSingle($single, $user);
				if ($r)
				{
					return true;
				}
			}
		}
		return false;

	}

	private function authorizeSingle($access, $user = null)
	{
		list($type, $name) = explode(':', $access);

		/* user role none is also acceptable one, and it always returns accepted */
		if ($type == 'role' && $name == 'none')
		{
			return true;
		}

		/* if no authentication has been done */
		if (!$this->get('username') && $user === null)
		{
			return false;
		}

		/* get user */
		$username = null;
		if ($user === null)
		{
			$user     = $this->getUser();
			$username = $user->get('username');
		}
		else
		{
			$username = $this->get('username');
		}

		/* if current user has root role, then everything is permitted always */
		if ($user->hasRole('role:root'))
		{
			return true;
		}

		/* if just authentication as any user is ok */
		if ($type == 'role' && $name == 'user')
		{
			return true;
		}

		/* if specific user is requested */
		if ($type == 'user')
		{
			if ($name == $username)
			{
				return true;
			}
			return false;
		}

		/* if specific user role is requested */
		if ($user->hasRole($access))
		{
			return true;
		}

		return false;
	}

	/**
	 * Get all users as user objects.
	 *
	 * @return array All users as array of objects, username as key.
	 */
	public function getUsers()
	{
		$users = array();

		/* loop through authenticator options to find requested user */
		foreach ($this->getModuleValue('authenticators') as $class)
		{
			$user  = new $class();
			$us    = $user->getUsers();
			$users = array_merge($us, $users);
		}

		return $users;
	}
}

/*! @} endgroup Core */
