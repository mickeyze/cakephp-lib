<?php
/**
 * Session class for Cake.
 *
 * Cake abstracts the handling of sessions.
 * There are several convenient methods to access session information.
 * This class is the implementation of those methods.
 * They are mostly used by the Session Component.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model.Datasource
 * @since         CakePHP(tm) v .0.10.0.1222
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Cake\Model\Datasource;
use Cake\Core\Configure;
use Cake\Utility\Hash;

/**
 * Session class for Cake.
 *
 * Cake abstracts the handling of sessions. There are several convenient methods to access session information.
 * This class is the implementation of those methods. They are mostly used by the Session Component.
 *
 * @package       Cake.Model.Datasource
 */
class Session {

/**
 * True if the Session is still valid
 *
 * @var boolean
 */
	public static $valid = false;

/**
 * Error messages for this session
 *
 * @var array
 */
	public static $error = false;

/**
 * User agent string
 *
 * @var string
 */
	protected static $_userAgent = '';

/**
 * Path to where the session is active.
 *
 * @var string
 */
	public static $path = '/';

/**
 * Error number of last occurred error
 *
 * @var integer
 */
	public static $lastError = null;

/**
 * Start time for this session.
 *
 * @var integer
 */
	public static $time = false;

/**
 * Cookie lifetime
 *
 * @var integer
 */
	public static $cookieLifeTime;

/**
 * Time when this session becomes invalid.
 *
 * @var integer
 */
	public static $sessionTime = false;

/**
 * Current Session id
 *
 * @var string
 */
	public static $id = null;

/**
 * Hostname
 *
 * @var string
 */
	public static $host = null;

/**
 * Session timeout multiplier factor
 *
 * @var integer
 */
	public static $timeout = null;

/**
 * Number of requests that can occur during a session time without the session being renewed.
 * This feature is only used when config value `Session.autoRegenerate` is set to true.
 *
 * @var integer
 * @see Cake\Model\Datasource\Session::_checkValid()
 */
	public static $requestCountdown = 10;

/**
 * Pseudo constructor.
 *
 * @param string $base The base path for the Session
 * @return void
 */
	public static function init($base = null) {
		static::$time = time();

		$checkAgent = Configure::read('Session.checkAgent');
		if (($checkAgent === true || $checkAgent === null) && env('HTTP_USER_AGENT') != null) {
			static::$_userAgent = md5(env('HTTP_USER_AGENT') . Configure::read('Security.salt'));
		}
		static::_setPath($base);
		static::_setHost(env('HTTP_HOST'));

		register_shutdown_function('session_write_close');
	}

/**
 * Setup the Path variable
 *
 * @param string $base base path
 * @return void
 */
	protected static function _setPath($base = null) {
		if (empty($base)) {
			static::$path = '/';
			return;
		}
		if (strpos($base, 'index.php') !== false) {
			 $base = str_replace('index.php', '', $base);
		}
		if (strpos($base, '?') !== false) {
			 $base = str_replace('?', '', $base);
		}
		static::$path = $base;
	}

/**
 * Set the host name
 *
 * @param string $host Hostname
 * @return void
 */
	protected static function _setHost($host) {
		static::$host = $host;
		if (strpos(static::$host, ':') !== false) {
			static::$host = substr(static::$host, 0, strpos(static::$host, ':'));
		}
	}

/**
 * Starts the Session.
 *
 * @return boolean True if session was started
 */
	public static function start() {
		if (static::started()) {
			return true;
		}
		static::init();
		$id = static::id();
		session_write_close();
		static::_configureSession();
		static::_startSession();

		if (!$id && static::started()) {
			static::_checkValid();
		}

		static::$error = false;
		return static::started();
	}

/**
 * Determine if Session has been started.
 *
 * @return boolean True if session has been started.
 */
	public static function started() {
		return isset($_SESSION) && session_id();
	}

/**
 * Returns true if given variable is set in session.
 *
 * @param string $name Variable name to check for
 * @return boolean True if variable is there
 */
	public static function check($name = null) {
		if (!static::started() && !static::start()) {
			return false;
		}
		if (empty($name)) {
			return false;
		}
		$result = Hash::get($_SESSION, $name);
		return isset($result);
	}

/**
 * Returns the Session id
 *
 * @param string $id
 * @return string Session id
 */
	public static function id($id = null) {
		if ($id) {
			static::$id = $id;
			session_id(static::$id);
		}
		if (static::started()) {
			return session_id();
		}
		return static::$id;
	}

/**
 * Removes a variable from session.
 *
 * @param string $name Session variable to remove
 * @return boolean Success
 */
	public static function delete($name) {
		if (static::check($name)) {
			static::_overwrite($_SESSION, Hash::remove($_SESSION, $name));
			return (static::check($name) == false);
		}
		static::_setError(2, __d('cake_dev', "%s doesn't exist", $name));
		return false;
	}

/**
 * Used to write new data to _SESSION, since PHP doesn't like us setting the _SESSION var itself
 *
 * @param array $old Set of old variables => values
 * @param array $new New set of variable => value
 * @return void
 */
	protected static function _overwrite(&$old, $new) {
		if (!empty($old)) {
			foreach ($old as $key => $var) {
				if (!isset($new[$key])) {
					unset($old[$key]);
				}
			}
		}
		foreach ($new as $key => $var) {
			$old[$key] = $var;
		}
	}

/**
 * Return error description for given error number.
 *
 * @param integer $errorNumber Error to set
 * @return string Error as string
 */
	protected static function _error($errorNumber) {
		if (!is_array(static::$error) || !array_key_exists($errorNumber, static::$error)) {
			return false;
		} else {
			return static::$error[$errorNumber];
		}
	}

/**
 * Returns last occurred error as a string, if any.
 *
 * @return mixed Error description as a string, or false.
 */
	public static function error() {
		if (static::$lastError) {
			return static::_error(static::$lastError);
		}
		return false;
	}

/**
 * Returns true if session is valid.
 *
 * @return boolean Success
 */
	public static function valid() {
		if (static::read('Config')) {
			if (static::_validAgentAndTime() && static::$error === false) {
				static::$valid = true;
			} else {
				static::$valid = false;
				static::_setError(1, 'Session Highjacking Attempted !!!');
			}
		}
		return static::$valid;
	}

/**
 * Tests that the user agent is valid and that the session hasn't 'timed out'.
 * Since timeouts are implemented in Session it checks the current static::$time
 * against the time the session is set to expire.  The User agent is only checked
 * if Session.checkAgent == true.
 *
 * @return boolean
 */
	protected static function _validAgentAndTime() {
		$config = static::read('Config');
		$validAgent = (
			Configure::read('Session.checkAgent') === false ||
			static::$_userAgent == $config['userAgent']
		);
		return ($validAgent && static::$time <= $config['time']);
	}

/**
 * Get / Set the userAgent
 *
 * @param string $userAgent Set the userAgent
 * @return void
 */
	public static function userAgent($userAgent = null) {
		if ($userAgent) {
			static::$_userAgent = $userAgent;
		}
		if (empty(static::$_userAgent)) {
			Session::init(static::$path);
		}
		return static::$_userAgent;
	}

/**
 * Returns given session variable, or all of them, if no parameters given.
 *
 * @param string|array $name The name of the session variable (or a path as sent to Set.extract)
 * @return mixed The value of the session variable
 */
	public static function read($name = null) {
		if (!static::started() && !static::start()) {
			return false;
		}
		if (is_null($name)) {
			return static::_returnSessionVars();
		}
		if (empty($name)) {
			return false;
		}
		$result = Hash::get($_SESSION, $name);

		if (isset($result)) {
			return $result;
		}
		static::_setError(2, "$name doesn't exist");
		return null;
	}

/**
 * Returns all session variables.
 *
 * @return mixed Full $_SESSION array, or false on error.
 */
	protected static function _returnSessionVars() {
		if (!empty($_SESSION)) {
			return $_SESSION;
		}
		static::_setError(2, 'No Session vars set');
		return false;
	}

/**
 * Writes value to given session variable name.
 *
 * @param string|array $name Name of variable
 * @param string $value Value to write
 * @return boolean True if the write was successful, false if the write failed
 */
	public static function write($name, $value = null) {
		if (!static::started() && !static::start()) {
			return false;
		}
		if (empty($name)) {
			return false;
		}
		$write = $name;
		if (!is_array($name)) {
			$write = array($name => $value);
		}
		foreach ($write as $key => $val) {
			static::_overwrite($_SESSION, Hash::insert($_SESSION, $key, $val));
			if (Hash::get($_SESSION, $key) !== $val) {
				return false;
			}
		}
		return true;
	}

/**
 * Helper method to destroy invalid sessions.
 *
 * @return void
 */
	public static function destroy() {
		if (static::started()) {
			session_destroy();
		}
		static::clear();
	}

/**
 * Clears the session, the session id, and renew's the session.
 *
 * @return void
 */
	public static function clear() {
		$_SESSION = null;
		static::$id = null;
		static::start();
		static::renew();
	}

/**
 * Helper method to initialize a session, based on Cake core settings.
 *
 * Sessions can be configured with a few shortcut names as well as have any number of ini settings declared.
 *
 * @return void
 * @throws Cake\Error\SessionException Throws exceptions when ini_set() fails.
 */
	protected static function _configureSession() {
		$sessionConfig = Configure::read('Session');
		$iniSet = function_exists('ini_set');

		if (isset($sessionConfig['defaults'])) {
			$defaults = static::_defaultConfig($sessionConfig['defaults']);
			if ($defaults) {
				$sessionConfig = Hash::merge($defaults, $sessionConfig);
			}
		}
		if (!isset($sessionConfig['ini']['session.cookie_secure']) && env('HTTPS')) {
			$sessionConfig['ini']['session.cookie_secure'] = 1;
		}
		if (isset($sessionConfig['timeout']) && !isset($sessionConfig['cookieTimeout'])) {
			$sessionConfig['cookieTimeout'] = $sessionConfig['timeout'];
		}
		if (!isset($sessionConfig['ini']['session.cookie_lifetime'])) {
			$sessionConfig['ini']['session.cookie_lifetime'] = $sessionConfig['cookieTimeout'] * 60;
		}
		if (!isset($sessionConfig['ini']['session.name'])) {
			$sessionConfig['ini']['session.name'] = $sessionConfig['cookie'];
		}
		if (!empty($sessionConfig['handler'])) {
			$sessionConfig['ini']['session.save_handler'] = 'user';
		}
		if (!isset($sessionConfig['ini']['session.gc_maxlifetime'])) {
			$sessionConfig['ini']['session.gc_maxlifetime'] = $sessionConfig['timeout'] * 60;
		}

		if (empty($_SESSION)) {
			if (!empty($sessionConfig['ini']) && is_array($sessionConfig['ini'])) {
				foreach ($sessionConfig['ini'] as $setting => $value) {
					if (ini_set($setting, $value) === false) {
						throw new Error\SessionException(sprintf(
							__d('cake_dev', 'Unable to configure the session, setting %s failed.'),
							$setting
						));
					}
				}
			}
		}
		if (!empty($sessionConfig['handler']) && !isset($sessionConfig['handler']['engine'])) {
			call_user_func_array('session_set_save_handler', $sessionConfig['handler']);
		}
		if (!empty($sessionConfig['handler']['engine'])) {
			$handler = static::_getHandler($sessionConfig['handler']['engine']);
			session_set_save_handler(
				array($handler, 'open'),
				array($handler, 'close'),
				array($handler, 'read'),
				array($handler, 'write'),
				array($handler, 'destroy'),
				array($handler, 'gc')
			);
		}
		Configure::write('Session', $sessionConfig);
		static::$sessionTime = static::$time + ($sessionConfig['timeout'] * 60);
	}

/**
 * Find the handler class and make sure it implements the correct interface.
 *
 * @param string $class
 * @return void
 * @throws Cake\Error\SessionException
 */
	protected static function _getHandler($class) {
		if (!class_exists($class)) {
			throw new Error\SessionException(__d('cake_dev', 'Could not load %s to handle the session.', $class));
		}
		$handler = new $class();
		if ($handler instanceof SessionHandlerInterface) {
			return $handler;
		}
		throw new Error\SessionException(__d('cake_dev', 'Chosen SessionHandler does not implement SessionHandlerInterface it cannot be used with an engine key.'));
	}

/**
 * Get one of the prebaked default session configurations.
 *
 * @param string $name
 * @return boolean|array
 */
	protected static function _defaultConfig($name) {
		$defaults = array(
			'php' => array(
				'cookie' => 'CAKEPHP',
				'timeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
					'session.cookie_path' => static::$path
				)
			),
			'cake' => array(
				'cookie' => 'CAKEPHP',
				'timeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
					'url_rewriter.tags' => '',
					'session.serialize_handler' => 'php',
					'session.use_cookies' => 1,
					'session.cookie_path' => static::$path,
					'session.auto_start' => 0,
					'session.save_path' => TMP . 'sessions',
					'session.save_handler' => 'files'
				)
			),
			'cache' => array(
				'cookie' => 'CAKEPHP',
				'timeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
					'url_rewriter.tags' => '',
					'session.auto_start' => 0,
					'session.use_cookies' => 1,
					'session.cookie_path' => static::$path,
					'session.save_handler' => 'user',
				),
				'handler' => array(
					'engine' => 'CacheSession',
					'config' => 'default'
				)
			),
			'database' => array(
				'cookie' => 'CAKEPHP',
				'timeout' => 240,
				'ini' => array(
					'session.use_trans_sid' => 0,
					'url_rewriter.tags' => '',
					'session.auto_start' => 0,
					'session.use_cookies' => 1,
					'session.cookie_path' => static::$path,
					'session.save_handler' => 'user',
					'session.serialize_handler' => 'php',
				),
				'handler' => array(
					'engine' => 'DatabaseSession',
					'model' => 'Session'
				)
			)
		);
		if (isset($defaults[$name])) {
			return $defaults[$name];
		}
		return false;
	}

/**
 * Helper method to start a session
 *
 * @return boolean Success
 */
	protected static function _startSession() {
		if (headers_sent()) {
			if (empty($_SESSION)) {
				$_SESSION = array();
			}
		} else {
			// For IE<=8
			session_cache_limiter("must-revalidate");
			session_start();
		}
		return true;
	}

/**
 * Helper method to create a new session.
 *
 * @return void
 */
	protected static function _checkValid() {
		if (!static::started() && !static::start()) {
			static::$valid = false;
			return false;
		}
		if ($config = static::read('Config')) {
			$sessionConfig = Configure::read('Session');

			if (static::_validAgentAndTime()) {
				static::write('Config.time', static::$sessionTime);
				if (isset($sessionConfig['autoRegenerate']) && $sessionConfig['autoRegenerate'] === true) {
					$check = $config['countdown'];
					$check -= 1;
					static::write('Config.countdown', $check);

					if ($check < 1) {
						static::renew();
						static::write('Config.countdown', static::$requestCountdown);
					}
				}
				static::$valid = true;
			} else {
				static::destroy();
				static::$valid = false;
				static::_setError(1, 'Session Highjacking Attempted !!!');
			}
		} else {
			static::write('Config.userAgent', static::$_userAgent);
			static::write('Config.time', static::$sessionTime);
			static::write('Config.countdown', static::$requestCountdown);
			static::$valid = true;
		}
	}

/**
 * Restarts this session.
 *
 * @return void
 */
	public static function renew() {
		if (session_id()) {
			if (session_id() != '' || isset($_COOKIE[session_name()])) {
				setcookie(Configure::read('Session.cookie'), '', time() - 42000, static::$path);
			}
			session_regenerate_id(true);
		}
	}

/**
 * Helper method to set an internal error message.
 *
 * @param integer $errorNumber Number of the error
 * @param string $errorMessage Description of the error
 * @return void
 */
	protected static function _setError($errorNumber, $errorMessage) {
		if (static::$error === false) {
			static::$error = array();
		}
		static::$error[$errorNumber] = $errorMessage;
		static::$lastError = $errorNumber;
	}

}
