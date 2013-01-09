<?php

class Minify_Requirements_Backend extends Requirements_Backend {
	
	static $rewrite_uris = true;
	
	function process_combined_files() {
		// The class_exists call prevents us from loading SapphireTest.php (slow) just to know that
		// SapphireTest isn't running :-)
		if(class_exists('SapphireTest', false)) $runningTest = SapphireTest::is_running_test();
		else $runningTest = false;
		
		if((Director::isDev() && !$runningTest && !isset($_REQUEST['combine'])) || !$this->combined_files_enabled) {
			return;
		}
		
		// Make a map of files that could be potentially combined
		$combinerCheck = array();
		foreach($this->combine_files as $combinedFile => $sourceItems) {
			foreach($sourceItems as $sourceItem) {
				if(isset($combinerCheck[$sourceItem]) && $combinerCheck[$sourceItem] != $combinedFile){ 
					user_error("Requirements_Backend::process_combined_files - file '$sourceItem' appears in two combined files:" .	" '{$combinerCheck[$sourceItem]}' and '$combinedFile'", E_USER_WARNING);
				}
				$combinerCheck[$sourceItem] = $combinedFile;
				
			}
		}

		// Work out the relative URL for the combined files from the base folder
		$combinedFilesFolder = ($this->getCombinedFilesFolder()) ? ($this->getCombinedFilesFolder() . '/') : '';

		// Figure out which ones apply to this pageview
		$combinedFiles = array();
		$newJSRequirements = array();
		$newCSSRequirements = array();
		foreach($this->javascript as $file => $dummy) {
			if(isset($combinerCheck[$file])) {
				$newJSRequirements[$combinedFilesFolder . $combinerCheck[$file]] = true;
				$combinedFiles[$combinerCheck[$file]] = true;
			} else {
				$newJSRequirements[$file] = true;
			}
		}
		
		foreach($this->css as $file => $params) {
			if(isset($combinerCheck[$file])) {
				$newCSSRequirements[$combinedFilesFolder . $combinerCheck[$file]] = true;
				$combinedFiles[$combinerCheck[$file]] = true;
			} else {
				$newCSSRequirements[$file] = $params;
			}
		}

		// Process the combined files
		$base = Director::baseFolder() . '/';
		foreach(array_diff_key($combinedFiles, $this->blocked) as $combinedFile => $dummy) {
			$fileList = $this->combine_files[$combinedFile];
			$combinedFilePath = $base . $combinedFilesFolder . '/' . $combinedFile;


			// Make the folder if necessary
			if(!file_exists(dirname($combinedFilePath))) {
				Filesystem::makeFolder(dirname($combinedFilePath));
			}
			
			// If the file isn't writebale, don't even bother trying to make the combined file
			// Complex test because is_writable fails if the file doesn't exist yet.
			if((file_exists($combinedFilePath) && !is_writable($combinedFilePath)) ||
				(!file_exists($combinedFilePath) && !is_writable(dirname($combinedFilePath)))) {
				user_error("Requirements_Backend::process_combined_files(): Couldn't create '$combinedFilePath'", E_USER_WARNING);
				continue;
			}

			 // Determine if we need to build the combined include
			if(file_exists($combinedFilePath) && !isset($_GET['flush'])) {
				// file exists, check modification date of every contained file
				$srcLastMod = 0;
				foreach($fileList as $file) {
					$srcLastMod = max(filemtime($base . $file), $srcLastMod);
				}
				$refresh = $srcLastMod > filemtime($combinedFilePath);
			} else {
				// file doesn't exist, or refresh was explicitly required
				$refresh = true;
			}

			if(!$refresh) continue;

			$combinedData = "";
			
			$moduleDir = $this->getModulePath();
			
			foreach(array_diff($fileList, $this->blocked) as $file) {
				$fileContent = file_get_contents($base . $file);
				// if we have a javascript file and jsmin is enabled, minify the content
				$isJS = stripos($file, '.js');
				if($isJS && $this->combine_js_with_jsmin) {
					
					require_once('../'.$moduleDir.'/thirdparty/jsmin/jsmin.php');
					increase_time_limit_to();
					$fileContent = JSMin::minify($fileContent);
					
				} else if (stripos($file, '.css')) { 
					
					// stolen shamelessly from Tonyair http://www.silverstripe.org/general-questions/show/14206
					require_once('../'.$moduleDir.'/thirdparty/min/lib/Minify/CSS.php'); 
					increase_time_limit_to();
					
					$minifyCSSConfig = array();
					
					// TODO: toggle with config option
					if (self::$rewrite_uris) {
						$minifyCSSConfig['currentDir'] = $base . dirname($file);
					}
					
					$fileContent = Minify_CSS::minify($fileContent, $minifyCSSConfig);
				}
				
				
				// write a header comment for each file for easier identification and debugging
				// also the semicolon between each file is required for jQuery to be combinable properly
				$combinedData .= "/****** FILE: $file *****/\n" . $fileContent . "\n".($isJS ? ';' : '')."\n";
			}

			$successfulWrite = false;
			$fh = fopen($combinedFilePath, 'wb');
			if($fh) {
				if(fwrite($fh, $combinedData) == strlen($combinedData)) $successfulWrite = true;
				fclose($fh);
				unset($fh);
			}

			// Unsuccessful write - just include the regular JS files, rather than the combined one
			if(!$successfulWrite) {
				user_error("Requirements_Backend::process_combined_files(): Couldn't create '$combinedFilePath'", E_USER_WARNING);
				continue;
			}
		}

		// @todo Alters the original information, which means you can't call this
		// method repeatedly - it will behave different on the second call!
		$this->javascript = $newJSRequirements;
		$this->css = $newCSSRequirements;
  }

	function getModulePath() {
		$path = dirname(__DIR__);
		$path = str_replace(BASE_PATH.DIRECTORY_SEPARATOR, '', $path);
		
		// for windows
		$path = str_replace('\\', '/', $path);
		
		return $path;
	}

	
}