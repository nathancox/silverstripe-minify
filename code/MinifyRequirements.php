<?php

class Minify_Requirements_Backend extends Requirements_Backend {

	/**
	 * @var bool
	 */
	private static $rewrite_uris = true;

	/**
	 * @param string $filename
	 * @param string $content
	 * @return string
	 */
	protected function minifyFile($filename, $content) {

		// if we have a javascript file and jsmin is enabled, minify the content
		$isJS = stripos($filename, '.js');
		if($isJS && $this->combine_js_with_jsmin) {

			increase_time_limit_to();
			$content = JSMin::minify($content);
		} else if (stripos($filename, '.css')) {

			increase_time_limit_to();
			$minifyCSSConfig = array();

			if (Config::inst()->get('Minify_Requirements_Backend', 'rewrite_uris')) {
				$minifyCSSConfig['currentDir'] = Director::baseFolder() . '/' . dirname($filename);
			}

			$content = Minify_CSS::minify($content, $minifyCSSConfig);
		}

		$content .= ($isJS ? ';' : '') . "\n";
		return $content;
	}

}
