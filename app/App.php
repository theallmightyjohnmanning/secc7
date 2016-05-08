<?php

/**
* This class serves as the container for the entire application.
* All initializations should happen from within in this class.
* It also serves functionality from other service containers.
*/

namespace SECC;

class App
{
	/**
	 * @return void
	 *
	 * Initializes all core components of the application in order.
	 */
	public static function initialize()
	{
		session_cache_limiter(false);
		session_start();
		
		self::service('Template')->initialize();
		self::service('Template')->render('test');
	}

	/**
	 * @param  string
	 * @return class
	 *
	 * Searches the models/accessors folder for the specified class and returns it.
	 */
	public static function accessor($class)
	{
		$accessor = 'SECC\\Models\\Accessors\\'.$class;
		if(class_exists($accessor))
			return $accessor::instance();
	}

	/**
	 * @param  string
	 * @return class
	 *
	 * Searches the models/services folder for the specified class and returns it.
	 */
	public static function service($class)
	{
		$service = 'SECC\\Models\\Services\\'.$class;
		if(class_exists($service))
			return $service::instance();
	}
}
