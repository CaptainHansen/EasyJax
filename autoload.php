<?
function autoload_API($class){
	$class_file = preg_replace("/\\\/",DIRECTORY_SEPARATOR,$class).".php";

	$path_try = __DIR__."/".$class_file;

	if(file_exists($path_try)){
		include($path_try);
		return;
	}

	//this sets this autoloader up to use your include path for autoloading classes.

	$paths = array();

	//adding all include paths (makes things... easier).
	$inc_paths = explode(":",get_include_path());
	foreach($inc_paths as $path){
		$paths[] = $path."/".$class_file;
	}
        
	foreach($paths as $path_try){
		if(file_exists($path_try)){
			include($path_try);
			return;
		}
	}
}

spl_autoload_register('autoload_API');