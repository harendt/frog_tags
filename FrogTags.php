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

class FrogTags {

	protected $name, $method, $page, $args, $content, $parent;

	/**
	 * Creates a new FrogTags object for $method. $method is the function that
	 * is called to generate the content of the tag.
	 */
	public function __construct($name, $method) {
		$this->name   = $name;
		$this->method = $method;
	}

	/**
	 * Generates the content for this tag using the Page object $page and the
	 * array of arguments $args.
	 */
	public function process($page, $args, $content, &$parent) {
		$this->page    = $page;
		$this->args    = $args;
		$this->content = $content;
		$this->parent  = &$parent;
		$method = $this->method;
		return $this->$method();
	}

	/**
	 * This method can be used inside of tag definitions to mark $arg as
	 * required. If $arg is missing an exception will be thrown. Else the
	 * passed value will be returned.
	 */
	protected function require_argument($arg) {
		if (!isset($this->args[$arg])) {
			throw new Exception("Missing argument \"$arg\"!");
		}
		return $this->args[$arg];
	}

	/**
	 * This method gets the argument $arg form the arguments array. If the
	 * argument was not passed, $default is returned.
	 */
	protected function get_argument($arg, $default = '') {
		if (isset($this->args[$arg]))
			return $this->args[$arg];
		else
			return $default;
	}

	/**
	 * This method can be used to simply run a tag parser inside of a tag
	 * definition.
	 */
	protected function parse($string, &$parent = NULL, $defaultArgs = array()) {
		$parser = new FrogTagsParser($this->page);
		return $parser->parse($string, $parent, $defaultArgs);
	}

	/**
	 * This method will parse the content of the tag and return the parsed
	 * content. This makes sense only for non-empty tags.
	 */
	protected function expand($defaultArgs = array()) {
		return $this->parse($this->content, $this, $defaultArgs);
	}

}

?>
