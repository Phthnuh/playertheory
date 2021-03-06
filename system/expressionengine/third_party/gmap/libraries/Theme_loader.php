<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Theme Loader
 *
 * A class that loads assets from the themes directory
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	Libraries
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/libraries/channel_data
 * @version		1.0
 * @build		20120103
 */
 
if(!class_exists('Theme_loader'))
{
	class Theme_loader {
		
		public $module_name;
		public $loaded_files = array();
		
		public function __construct($data = array())
		{
			$this->EE =& get_instance();
			
			if(isset($data['module_name']))
				$this->module_name = $data['module_name'];
	
			/* Url Validation */
			$this->url_format = 

			'/^(https?):\/\/'.                                         // protocol
			'(([a-z0-9$_\.\+!\*\'\(\),;\?&=-]|%[0-9a-f]{2})+'.         // username
			'(:([a-z0-9$_\.\+!\*\'\(\),;\?&=-]|%[0-9a-f]{2})+)?'.      // password
			'@)?(?#'.                                                  // auth requires @
			')((([a-z0-9][a-z0-9-]*[a-z0-9]\.)*'.                      // domain segments AND
			'[a-z][a-z0-9-]*[a-z0-9]'.                                 // top level domain  OR
			'|((\d|[1-9]\d|1\d{2}|2[0-4][0-9]|25[0-5])\.){3}'.
			'(\d|[1-9]\d|1\d{2}|2[0-4][0-9]|25[0-5])'.                 // IP address
			')(:\d+)?'.                                                // port
			')(((\/+([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)*'. // path
			'(\?([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)'.      // query string
			'?)?)?'.                                                   // path and query string optional
			'(#([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)?'.      // fragment
			'$/i';
			
		}
		
		public function theme_path()
		{
			return $this->EE->config->item('theme_folder_path');
		}
		
		public function theme_url()
		{
			return $this->EE->config->item('theme_folder_url');
		}
		
		public function javascript($file, $directory = 'javascript')
		{
			$file = $this->_prep_url($directory, $file, '.js');
			
			if(!in_array($file, $this->loaded_files))
			{
				$this->loaded_files[] = $file;
			
				$this->EE->cp->add_to_head('<script type="text/javascript" src="'.$file.'"></script>');
			}
		}
		
		public function css($file, $directory = 'css')
		{	
			$file = $this->_prep_url($directory, $file, '.css');
			
			if(!in_array($file, $this->loaded_files))
			{
				$this->loaded_files[] = $file;
			
				$this->EE->cp->add_to_head('<link type="text/css" href="'.$file.'" rel="stylesheet" media="screen" />');
			}
		}
		
		private function _prep_url($directory, $file, $ext)
		{
			if(!$this->is_valid_url($file))
			{
				$file 	= str_replace('.js', '', $file);
				$file 	= $this->theme_url() . 'third_party/' . $this->module_name . '/' . $directory . '/' . $file . $ext;
			}
			
			return $file;	
		}
		
		/**
		 * Verify the syntax of the given URL. 
		 * 
		 * @access public
		 * @param $url The URL to verify.
		 * @return boolean
		 */
		private function is_valid_url($url) {
		  if ($this->str_starts_with(strtolower($url), 'http://localhost')) {
		    return true;
		  }
		  return preg_match($this->url_format, $url);
		}
	
		/**
		 * String starts with something
		 * 
		 * This function will return true only if input string starts with
		 * niddle
		 * 
		 * @param string $string Input string
		 * @param string $niddle Needle string
		 * @return boolean
		 */
		private function str_starts_with($string, $niddle) {
		      return substr($string, 0, strlen($niddle)) == $niddle;
		}
	
		
	}
}