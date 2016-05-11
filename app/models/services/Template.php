<?php

/**
* This class is used to parse template files and construct
* the final document so that it can be read properly by 
* the browser & is responisible for document structure.
*/

namespace SECC\Models\Services;

class Template
{

	// Used for implementing a singleton pattern
	private static $instance = null;

	private static 	$templateDirectory 	= '', // Stores a platform independent path to the view directory
					$cachedDirectory 	= '', // Stores a platform independent path to the cached view directory
					$extension			= ''; // Stores a reference to the specified file extension of the template

	/**
	 * Creates an instance of this class
	 * @return Template class singleton
	 */
	public static function instance()
	{
		return (isset($instance)) ? self::$instance : self::$instance = new Template;
	}

	/**
	 * Set the dynamic elements of the temlate system
	 * @param  string $views Set the path to the template files container
	 * @param  string $cache Set the path to the template cached files container
	 * @param  string $ext   Set the file extension used for template files
	 */
	public static function initialize($views = '..'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR, $cache = '..'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR, $ext = '.html')
	{
		self::$templateDirectory	= $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.$views;
		self::$cachedDirectory 		= $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.$cache;
		self::$extension 			= $ext;
	}

	/**
	 * Used to display a template to the browser
	 * @param  string $template The path to the template file (dot seperated)
	 */
	public static function render($template = '')
	{
		if(!empty($template))
		{
			$cachedFileName = md5(File::lastDotFormatedEntry($template).self::$extension);
			$template 		= File::dotsToSlashes($template);
			if(self::compile($template))
			{
				$template = File::lastDotFormatedEntry($template);
				require_once self::$cachedDirectory.$cachedFileName.self::$extension;
			}
			else
				throw new \Exception('A compilation error occured!', 1);;
				
		}
		else
			throw new \Exception('No template file specified!', 1);		
	}

	/**
	 * Returns the string of the specified template file
	 * @param  string $template Path to the template file
	 * @return string           The template file as a string
	 */
	public static function make($template = '')
	{
		if(!empty($template))
		{
			if(self::compile($template))
			{
				$template = File::lastDotFormatedEntry($template);
				return self::$cachedDirectory.$template.self::$extension;
			}
			else
				throw new \Exception('A compilation error occured!', 1);;
				
		}
		else
			throw new \Exception('No template file specified!', 1);		
	}

	/**
	 * Compiles a template file into a file that php can parse
	 * @param  string $template The template file as a string
	 * @return bool           Used to check if the file has been successfully compiled
	 */
	public static function compile($template = '')
	{
		$templateFileName 	= File::lastDotFormatedEntry($template); // Get the actual file name from the params
		$template 			= File::dotsToSlashes($template); // Convert dots in params to slashes
		$cachedFileName 	= md5($template.self::$extension);
		if(file_exists(self::$templateDirectory.$template.self::$extension))
			$templateAge = File::age(self::$templateDirectory.$template.self::$extension);

		if(file_exists(self::$cachedDirectory.$cachedFileName.self::$extension))
			$cachedAge = File::age(self::$cachedDirectory.$cachedFileName.self::$extension);

		if(isset($templateAge) && isset($cachedAge))
		{
			// Load the template from the cached file if it is the 
			// same age as it's cached version
			if($templateAge === $cachedAge)
			{
				$template = file_get_contents(self::$cachedDirectory.$cachedFileName.self::$extension);
				return true;
			}
			else // Sync the cached template file to it's updated version
			{
				$template = file_get_contents(self::$templateDirectory.$template.self::$extension);
				$template = self::scanDocument($template);
				file_put_contents(self::$cachedDirectory.$cachedFileName.self::$extension, $template);
				return true;
			}
		}
		else if(isset($templateAge)) // Parse the template file, cache it and load the cached version
		{
			$template = file_get_contents(self::$templateDirectory.$template.self::$extension);
			$template = self::scanDocument($template);
			file_put_contents(self::$cachedDirectory.$cachedFileName.self::$extension, $template);
			return true;
		}
		
		return false;
	}

	// 
	public static function scanDocument($template = '')
	{
		$template = self::scanForExtensions($template);
		$template = self::scanForForIncludes($template);
		$template = self::scanForUseStatments($template);
		$template = self::scanForUnescapedEchos($template);
		$template = self::scanForEscapedEchos($template);
		$template = self::scanForIfStatements($template);
		$template = self::scanForSwitchStatements($template);
		$template = self::scanForForLoops($template);
		$template = self::scanForForeachLoops($template);
		$template = self::scanForWhileLoops($template);
		return $template;
	}

	public static function scanForExtensions($template = '')
	{
		preg_match('/@extends\((.+)\)/', $template, $out);
		if(isset($out[0]))
		{
			$file = File::lastDotFormatedEntry($out[1]);
			$file = File::dotsToSlashes($out[1]);
			$file = preg_replace('/\'/', '', $file);

			if(file_exists(self::$templateDirectory.$file.self::$extension))
			{
				$temp = $template;
				$template = file_get_contents(self::$templateDirectory.$file.self::$extension);

				preg_match_all('/@section\((.+)\)/', $template, $yields);
				preg_match_all('/@section\((.+)\)/', $temp, $titles);

				$yields = $yields[0];
				$titles = $titles[0];

				$sections = preg_split('/@section/', $temp);
				$sections = preg_replace('/\((.+)\)/', '', $sections);		

				foreach($yields as $x => $yield)
				{
					foreach($titles as $y => $title)
					{
						if($yield === $title)
						{
							$template = str_replace($yield, $sections[$y+1], $template);
						}
					}
				}
			}
		}

		$template = preg_replace('/(?:(?:\r\n|\r|\n)\s*){2}/s', "\n\n", $template);

		return $template;
	}

	public static function scanForForIncludes($template = '')
	{
		preg_match_all('/@include\((.+)\)/', $template, $out);

		for($i = 0; $i < count($out[0]); $i++)
		{
			$fileName 		= File::lastDotFormatedEntry($file);
			$cachedFileName = md5($fileName.self::$extension);
			$file 			= File::dotsToSlashes($out[1][$i]);
			$file 			= preg_replace('/\'/', '', $file);

			if(file_exists(self::$templateDirectory.$fileName.self::$extension))
				$templateAge = File::age(self::$templateDirectory.$fileName.self::$extension);

			if(file_exists(self::$cachedDirectory.$cachedFileName.self::$extension))
				$cachedAge = File::age(self::$cachedDirectory.$cachedFileName.self::$extension);

			if($templateAge === $cachedAge)
			{
				$template = str_replace($out[0][$i], '<?php include \''.self::$cachedDirectory.$cachedFileName.self::$extension.'\'; ?>', $template);
			}
			else if($templateAge)
			{
				$partial = file_get_contents(self::$templateDirectory.$file.self::$extension);
				$partial = self::scanDocument($partial);
				file_put_contents(self::$cachedDirectory.$cachedFileName.self::$extension, $partial);
				$template = str_replace($out[0][$i], '<?php include \''.self::$cachedDirectory.$cachedFileName.self::$extension.'\'; ?>', $template);
			}
			else
			{
				throw new \Exception('The partial template file'.self::$templateDirectory.$file.self::$extension.'could not be found', 1);
			}
		}

		return $template;
	}

	public static function scanForUseStatments($template = '')
	{
		preg_match_all('/@use\((.+)\)/', $template, $out);

		for($i = 0; $i < count($out[0]); $i++)
			$template = str_replace($out[0][$i], '<?php use '.$out[1][$i].'; ?>', $template);

		return $template;
	}

	public static function scanForUnescapedEchos($template = '')
	{
		preg_match_all('/\{!!(.+)!!\}/', $template, $out);

		for($i = 0; $i < count($out[0]); $i++)
			$template = str_replace($out[0][$i], '<?= '.$out[1][$i].'; ?>', $template);

		return $template;
	}

	public static function scanForEscapedEchos($template = '')
	{
		preg_match_all('/\{\{(.+)\}\}/', $template, $out);

		for($i = 0; $i < count($out[0]); $i++)
			$template = str_replace($out[0][$i], "<?= htmlentities(".$out[1][$i].", ENT_QUOTES, 'UTF-8'); ?>", $template);

		return $template;
	}

	public static function scanForIfStatements($template = '')
	{
		preg_match_all('/@if\((.+)\)/', $template, $out);

		for($i = 0; $i < count($out[0]); $i++)
			$template = str_replace($out[0][$i], '<?php if('.$out[1][$i].'): ?>', $template);

		$template = str_replace('@endif', '<?php endif; ?>', $template);

		return $template;
	}

	public static function scanForSwitchStatements($template = '')
	{
		preg_match_all('/@switch\((.+)\)/', $template, $out);
		preg_match_all('/@case\((.+)\)/', $template, $case);

		for($i = 0; $i < count($out[0]); $i++)
			$template = str_replace($out[0][$i], '<?php switch('.$out[1][$i].') { ?>', $template);

		for($i = 0; $i < count($case[0]); $i++)
			$template = str_replace($case[0][$i], '<?php case'.$case[1][$i].': ?>', $template);

		$template = str_replace('@break', '<?php break; ?>', $template);
		$template = str_replace('@endswitch', '<?php } ?>', $template);

		return $template;
	}

	public static function scanForForLoops($template = '')
	{
		preg_match_all('/@for\((.+)\)/', $template, $out);

		for($i = 0; $i < count($out[0]); $i++)
			$template = str_replace($out[0][$i], '<?php for('.$out[1][$i].'): ?>', $template);

		$template = str_replace('@endfor', '<?php endfor; ?>', $template);

		return $template;
	}

	public static function scanForForeachLoops($template = '')
	{
		preg_match_all('/@foreach\((.+)\)/', $template, $out);

		for($i = 0; $i < count($out[0]); $i++)
			$template = str_replace($out[0][$i], '<?php foreach('.$out[1][$i].'): ?>', $template);

		$template = str_replace('@endforeach', '<?php endforeach; ?>', $template);

		return $template;
	}

	public static function scanForWhileLoops($template = '')
	{
		preg_match_all('/@while\((.+)\)/', $template, $out);

		for($i = 0; $i < count($out[0]); $i++)
			$template = str_replace($out[0][$i], '<?php while('.$out[1][$i].'): ?>', $template);

		$template = str_replace('@endwhile', '<?php endwhile; ?>', $template);

		return $template;
	}
}
