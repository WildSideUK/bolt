<?php

namespace Bolt\Twig\Handler;

use Bolt\Library as Lib;
use Silex;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Bolt specific Twig functions and filters that provide generic utility
 *
 * @internal
 */
class UtilsHandler
{
    /** @var \Silex\Application */
    private $app;

    /**
     * @param \Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    /**
     * Check if a file exists.
     *
     * @param string $filename
     *
     * @return boolean
     */
    public function fileExists($filename)
    {
        return file_exists($filename);
    }

    /**
     * Output pretty-printed backtrace.
     *
     * @param integer $depth
     *
     * @return string|null
     */
    public function printBacktrace($depth)
    {
        if (!$this->allowDebug()) {
            return null;
        }

        return VarDumper::dump(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $depth));
    }

    /**
     * Send debug data to the developers FirePHP instance in-browser.
     *
     * @param mixed $var  The data to be dumped into FirePHP
     * @param mixed $msg  The message to associate with the data
     *
     * @return string FirePHP formatted string
     */
    public function printFirebug($var, $msg)
    {
        if (!$this->allowDebug()) {
            return null;
        }

        if (is_string($msg)) {
            $this->app['logger.firebug']->info($msg, (array) $var);
        } elseif (is_string($var)) {
            $this->app['logger.firebug']->info($var, (array) $msg);
        }
    }

    /**
     * Redirect the browser to another page.
     *
     * @param string $path
     *
     * @return string
     */
    public function redirect($path)
    {
        Lib::simpleredirect($path);

        return '';
    }

    /**
     * Return the requested parameter from $_REQUEST, $_GET or $_POST.
     *
     * @param string  $parameter    The parameter to get
     * @param string  $from         'GET' or 'POST', all the others falls back to REQUEST.
     * @param boolean $stripSlashes Apply stripslashes. Defaults to false.
     *
     * @return mixed
     */
    public function request($parameter, $from = '', $stripSlashes = false)
    {
        $from = strtoupper($from);

        if ($from === 'GET') {
            $request = $this->app['request']->query->get($parameter, false);
        } elseif ($from === 'POST') {
            $request = $this->app['request']->request->get($parameter, false);
        } else {
            $request = $this->app['request']->get($parameter, false);
        }

        if ($stripSlashes) {
            $request = stripslashes($request);
        }

        return $request;
    }

    /**
     * Helper function to determine if we're supposed to allow `backtrace`
     * and `firebug`. If `$this->app['debug']` is false, we don't allow it.
     * Otherwise we show only to _logged on_ users, _or_ non-authenticated
     * users, but then `debug_show_loggedoff` needs to be set.
     *
     * @return boolean
     */
    private function allowDebug()
    {
        $debug = $this->app['debug'];
        $isUser = (bool) $this->app['users']->getCurrentUser() ?: false;
        $showAlways = $this->app['config']->get('general/debug_show_loggedoff', false);

        return $debug && ($isUser || $showAlways);
    }
}
