<?php

/**
 * Copyright 2009 Bastian Harendt <b.harendt@gmail.com>
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

class FrogTagsParserPreprocessor {

	/**
	 * Runs the preprocessor. The preprocessor has two assignments.
	 *
	 * First it replaces empty tags that correspond to the pattern
	 * <f:name ... /> with <f:name ...></f:name>.
	 *
	 * Secondly it marks top-level tags with sign '@' before the tag's name.
	 * Top-level tags are tags that are not surrounded by other tags. As the
	 * tag parser will only parse marked tags it will only parse top-level
	 * tags.
	 *
	 * The content of the top-level tags can afterwards be parsed inside of the
	 * tag definition using the method 'expand' of class FrogTags.
	 */
	public function run($string) {
		try {
			// replace empty tags
			$string = preg_replace("|<f:(\w+?)([^<>]*)/>|U", "<f:\\1\\2></f:\\1>", $string);

			// mark top-level tags
			$this->parentTags = array();
			$string = preg_replace_callback("|<(/?)f:(\w+?)([^<>]*)>|U", array($this, 'process_tag'), $string);
		}
		catch (Exception $e) {
			$controller = new FrogTagsController();
			$controller->error_page('Fatal Parsing Error', $e->getMessage());
		}
		return $string;
	}

	/**
	 * Marks top-level tags with sign '@' before the tag's name.
	 */
	private function process_tag($matches) {
		$prefix    = $matches[1];
		$name      = $matches[2];
		$arguments = $matches[3];
		if ($prefix == '')
			$this->open_tag($name);
		elseif($prefix == '/')
			$this->close_tag($name);
		else
			throw new Exception('Unknown prefix in <code>' . htmlspecialchars($matches[0]) . '</code>!');
		return '<' . $prefix . 'f:' . $name . $arguments . '>';
	}

	/**
	 * Array to store all the opened tags (in order of appearance).
	 */
	private $parentTags;

	/**
	 * Marks a start tag if it is a top-level tag.
	 */
	private function open_tag(&$name) {
		array_push($this->parentTags, $name);

		// mark start tag if it is a top-level tag
		if (count($this->parentTags) == 1)
			$name = '@'.$name;
	}

	/**
	 * Marks an end tag if it is a top-level tag.
	 */
	private function close_tag(&$name) {
		if (!in_array($name, $this->parentTags)) {
			print_r($this->parentTags);
			throw new Exception("End tag for element 'f:$name' which is not open!");
		}
		if (end($this->parentTags) != $name)
			throw new Exception("Expected end tag for element 'f:" . end($this->parentTags) . "' but got end tag for element 'f:$name'!");
		array_pop($this->parentTags);

		// mark end tag if it is a top-level tag
		if (count($this->parentTags) == 0)
			$name = '@'.$name;
	}

}

?>
