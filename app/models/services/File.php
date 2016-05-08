<?php
/**
*
*/
namespace SECC\Models\Services;
class File
{
	private static $instance = null;
	
	public static function instance()
	{
		if(!isset(self::$instance))
			self::$instance = new self;
		return self::$instance;
	}

	public static function dotsToSlashes($string = '')
	{
		$string = explode('.', $string);
		$string = implode(DIRECTORY_SEPARATOR, $string);
		return $string;
	}

	public static function lastDotFormatedEntry($string = '')
	{
		$string = explode('.', $string);
		array_reverse($string);
		return $string[0];
	}

	public static function age($file = '')
	{
		return date('F d Y H:i:s', filemtime($file));
	}
	
	public static function mkdir($path)
	{
		if(is_dir($path)) return true;
		$prev_path = substr($path, 0, strrpos($path, '/', -2) + 1);
		$return = self::mkdir($prev_path);
		return ($return && is_writable($prev_path)) ? mkdir($path) : false;
	}
}