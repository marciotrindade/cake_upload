<?php
/**
 * @filesource
 * @author     Marcio Trindade <marciotrindade@gmail.com>
 * @package    cake
 * @subpackage components
 * @version    1.0
 */

class UploadComponent extends Object
{
	var $sub_dir_is_sequencial = true;
	

	function startup(&$controller)
	{
		$this->controller =& $controller;
	}

	function process()
	{
		$root_dir = Inflector::tableize($this->controller->name);
		$root_dir = WWW_ROOT."files/{$root_dir}";  

		if (! file_exists($root_dir)) 
		{
			mkdir($root_dir);
			chmod($root_dir, 0777);
		}
		$model_name = Inflector::classify($this->controller->name);
		$root_dir = "{$root_dir}/{$this->controller->data[$model_name]["id"]}";
		if (! file_exists($root_dir)) 
		{
			mkdir($root_dir);
			chmod($root_dir, 0777);
		}

		$i = -1;
		foreach ($this->controller->files as $field => $files)
		{
			$i++;
			if ($this->sub_dir_is_sequencial)
			{
				$dir = "{$root_dir}/{$i}";
			}
			else
			{
				$dir = "{$root_dir}/{$field}";
			}
			if (! file_exists($dir)) 
			{
				mkdir($dir);
				chmod($dir, 0777);
			}

			$model_name = Inflector::singularize($this->controller->name);
			$field = $this->controller->data[$model_name][$field];
			if ((isset($field['error']) && $field['error'] == 0) || (!empty($field['tmp_name']) && $field['tmp_name'] != 'none'))
			{
				foreach ($files as $name => $options)
				{
					$options = am(array("ext" => "jpg", "name" => $name, "mime" => "image/jpeg", "quality" => 90, "type" => "move"), $options);
					$options["file_name"] = "{$dir}/{$options["name"]}.{$options["ext"]}";
					$function = "{$options["type"]}_file";
					$this->$function($field["tmp_name"], $options);
				}
				@unlink($field["tmp_name"]);
			}			
		}
	}

	function re_process($id)
	{
		$name_dir = Inflector::tableize($this->controller->name);
		$root_dir = WWW_ROOT."files/{$name_dir}/{$id}";

		$i = -1;
		foreach ($this->controller->files as $field => $files)
		{
			$i++;
			if ($sub_dir_is_sequencial)
			{
				$dir = "{$root_dir}/{$i}";
			}
			else
			{
				$dir = "{$root_dir}/{$field}";
			}
			if (! file_exists($dir)) 
			{
				continue;
			}

			$origin = "{$dir}/origin.jpg";
			
			if (file_exists($origin))
			{
				foreach ($files as $name => $options)
				{
					if ($name == "origin")
					{
						continue;
					}
					$options = am(array("ext" => "jpg", "name" => $name, "mime" => "image/jpeg", "quality" => 90), $options);
					$options["file_name"] = "{$dir}/{$options["name"]}.{$options["ext"]}";
					$function = "{$options["type"]}_file";
					$this->$function($origin, $options);
				}
			}
		}
	}

	// files
	function fixed_file($file, $options)
	{
		$this->get_image();

		$this->Image->destinationQuality = $options["quality"];
		$this->Image->setSourceFile($file);
		$this->Image->resizeFixed($options["width"], $options["height"]);
		$this->Image->createFile($options["file_name"], $options["mime"]);
	}
	
	function resize_file($file, $options)
	{
		$this->get_image();
		
		$this->Image->destinationQuality = $options["quality"];
		$this->Image->setSourceFile($file);
		$this->Image->resizeImage($options["width"], $options["height"]);
		$this->Image->createFile($options["file_name"], $options["mime"]);
	}
	
	function crop_file($file, $options)
	{
		$this->get_image();
		
		$this->Image->destinationQuality = $options["quality"];
		$this->Image->setSourceFile($file);
		$this->Image->resizeImageMin($options["width"], $options["height"]);
		$this->Image->createFile($options["file_name"], $options["mime"]);
		
		$this->Image->setSourceFile($options["file_name"]);
		$this->Image->cropImage(array($options["width"], $options["height"]));
		$this->Image->createFile($options["file_name"], $options["mime"]);
	}

	function move_file($file, $options)
	{
		@copy($file, $options["file_name"]);
		@chmod($options["file_name"], 0777);
	}

	function get_image()
	{
		if (!isSet($this->Image))
		{
			APP::import('Component', 'CakeUpload.Image');
			$this->Image = new ImageComponent();
		}
	}

}
?>