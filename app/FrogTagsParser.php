<?php

/**
 * Copyright 2009-2010 Bastian Harendt <b.harendt@gmail.com>
 *
 * This file is part of FrogTags Plugin.
 *
 * FrogTags Plugin is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 *
 * FrogTags Plugin is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * FrogTags Plugin.  If not, see <http://www.gnu.org/licenses/>.
 *
 * FrogTags Plugin was designed for Frog CMS at version 0.9.5.
 */

include_once('FrogTags.php');
include_once('FrogTagsList.php');
include_once('FrogTagsParserPreprocessor.php');

class FrogTagsParser {

	protected $page, $tags;

	/**
	 * Creates a new FrogTagParser object for $page.
	 */
	public function __construct($page) {
		$this->page = $page;
		$this->tags = FrogTagsList::get();
	}

	/**
	 * Parses a string that includes the a tag reference and returns an array
	 * that contains all passed arguments.
	 */
	private function parse_arguments($tag) {
		preg_match_all('|(\w+?)="(.*)"|Us', $tag, $matches, PREG_SET_ORDER);
		$args = array();
		foreach ($matches as $match) {
			$args[$match[1]] = $match[2];
		}
		return $args;
	}

	/**
	 * Runs the tag parser on $string and returns a string with all the tags
	 * replaced by their generated content.
	 *
	 * The parameter $parent specifies the parent tag.
	 *
	 * The array $defaultArgs can be used the set default arguments. These
	 * values will be overwritten if they are passed in the tag reference
	 * aswell.
	 */
	public function parse($string, &$parent = NULL, $defaultArgs = array()) {

		// Preprocessing
		$preprocessor = new FrogTagsParserPreprocessor();
		$string = $preprocessor->run($string);

		// Parse top-level tags. Top-level tags have been marked with sign '@'
		// by the preprocessor.
		preg_match_all("|<f:@(\w+?)([^<>]*)>(.*)</f:@\\1\s*>|Us", $string, $matches, PREG_SET_ORDER);
		foreach($matches as $match) {
			$reference = $match[0];
			$name      = $match[1];
			$content   = $match[3];
			$args      = array_merge(
				$defaultArgs,
				$this->parse_arguments($match[2])
			);
			try {
				if (isset($this->tags[$name])) {
					$class  = $this->tags[$name]['class'];
					$method = $this->tags[$name]['method'];
					$tag = new $class($name, $method);
					$tag = $tag->process(clone $this->page, $content, $args, $parent);
				}
				else {
					throw new Exception("Tag \"&lt;f:$name /&gt;\" wasn't defined!");
				}
			}
			catch (Exception $e) {
				$tag = "An error occured in tag \"$name\"! " . $e->getMessage();
			}
			$string = str_replace($reference, $tag, $string);
		}

		// Postprocessing (remove empty paragraphs left by Textile)
		$string = preg_replace('|<p>\s*</p>|', '', $string);

		return $string;
	}
}

?>
