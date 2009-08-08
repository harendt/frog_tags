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

	protected $name, $method, $page, $content, $args, $parent;

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
	public function process($page, $content, $args, &$parent) {
		$this->page    = $page;
		$this->content = $content;
		$this->args    = $args;
		$this->parent  = &$parent;

		$method = $this->method;
		return $this->$method();
	}

	/**
	 * This method can be used inside of tag definitions to mark $arg as
	 * required. If $arg is missing an exception will be thrown. Otherwise the
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
	 * This method can be used inside of tag definitions to require a
	 * particular parent tag. If the required parent tag is missing an
	 * exception will be thrown. Otherwise the required parent tag will be
	 * returned.
	 */
	protected function require_parent($parentTag) {
		if ($this->parent == NULL)
			throw new Exception("This tag requires a parent tag \"$parentTag\"!");
		elseif ($this->parent->name == $parentTag)
			return $this->parent;
		else 
			$this->parent->require_parent($parentTag);
	}

	/**
	 * This method can be used inside of tag definitions to require that the
	 * class attribute $member has been defined in any parent tag.
	 *
	 * If the parameter $parentTag is given the required class attribute must
	 * belong to that particular tag.
	 *
	 * If the required attribute is missing an exception will be thrown.
	 * Otherwise the required attribute will be returned and $parentTag will
	 * refer to the parent tag with the required attribute.
	 */
	protected function require_class_attribute($member, &$parentTag = NULL) {
		if ($this->parent == NULL) {
			throw new Exception("This tag requires a class attribute \"$member\" in parent tag!");
		}
		elseif (($parentTag == NULL or $this->parent->name == $parentTag) and property_exists($this->parent, $member) and isset($this->parent->$member)) {
			$parentTag = $this->parent;
			return $this->parent->$member;
		}
		else  {
			$this->parent->require_class_attribute($member, $parentTag);
		}
	}

	/**
	 * This method can be used to simply run a tag parser inside of a tag
	 * definition. If a filter id is given the specified filter will be applied
	 * aswell.
	 */
	protected function parse($string, &$parent = NULL, $filter_id = '', $defaultArgs = array()) {
		if (!empty($string)) {
			$parser = new FrogTagsParser($this->page);
			$string = $parser->parse($string, $parent, $defaultArgs);
		}
		if (!empty($filter_id)) {
			$filter = Filter::get($filter_id);
			$string = $filter->apply($string);
		}
		return $string;
	}

	/**
	 * This method will parse the content of the tag and return the parsed
	 * content. This makes sense only for non-empty tags.
	 */
	protected function expand($defaultArgs = array()) {
		return $this->parse($this->content, $this, '', $defaultArgs);
	}

}

?>
