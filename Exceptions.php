<?php

/*! \addtogroup Exceptions
 * @{
 */

/**
 * Redirect exception.
 */
class RedirectException extends Exception
{
    public function __construct($url, $code = 302, $get = array())
    {
        if (count($get) > 0) {
            $url .= '?' . http_build_query($get);
        }
        parent::__construct($url, $code);
    }
}

/**
 * Return http code 304 (Not Modified).
 */
class Exception304 extends Exception
{
    public function __construct($message = 'Not Modified')
    {
        parent::__construct($message, 304);
    }
}

/**
 * Return http code 400 (Bad Request).
 */
class Exception400 extends Exception
{
    public function __construct($message = 'Bad Request')
    {
        parent::__construct($message, 400);
    }
}

/**
 * Return http code 401 (Unauthorized).
 */
class Exception401 extends Exception
{
    public function __construct($message = 'Unauthorized')
    {
        parent::__construct($message, 401);
    }
}

/**
 * Return http code 403 (Forbidden).
 */
class Exception403 extends Exception
{
    public function __construct($message = 'Forbidden')
    {
        parent::__construct($message, 403);
    }
}

/**
 * Return http code 404 (Not Found).
 */
class Exception404 extends Exception
{
    public function __construct($message = 'Not Found')
    {
        parent::__construct($message, 404);
    }
}

/**
 * Return http code 405 (Method Not Allowed).
 */
class Exception405 extends Exception
{
    public function __construct($message = 'Method Not Allowed')
    {
        parent::__construct($message, 405);
    }
}

/**
 * Return http code 409 (Conflict).
 */
class Exception409 extends Exception
{
    public function __construct($message = 'Conflict')
    {
        parent::__construct($message, 409);
    }
}

/**
 * Return http code 410 (Gone).
 */
class Exception410 extends Exception
{
    public function __construct($message = 'Gone')
    {
        parent::__construct($message, 410);
    }
}

/**
 * Return http code 500 (Internal Server Error).
 */
class Exception500 extends Exception
{
    public function __construct($message = 'Internal Server Error')
    {
        parent::__construct($message, 500);
    }
}

/**
 * Return http code 501 (Not Implemented).
 */
class Exception501 extends Exception
{
    public function __construct($message = 'Not Implemented')
    {
        parent::__construct($message, 501);
    }
}

/*! @} endgroup Exceptions */
