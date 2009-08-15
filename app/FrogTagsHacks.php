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

Observer::observe('page_found', 'FrogTagsHacks::infiltrate');

Observer::stopObserving('page_not_found', 'behavior_page_not_found');
Observer::observe('page_not_found', 'FrogTagsHacks::page_not_found_hack');

class FrogTagsHacks {

	/**
	 * Function to be called by the observer. This function will call the main
	 * frog tags parser on the content of $page.
	 */
	public static function infiltrate($page) {
		// define constant 'page_found' (see page_not_found_hack to know why ...)
		define('page_found', true);
		// call other observer methods first
		$observerList = Observer::getObserverList('page_found');
		$alreadyCalled = true;
		foreach($observerList as $callback) {
			if ($callback == 'FrogTagsHacks::infiltrate')
				$alreadyCalled = false;
			elseif (!$alreadyCalled) {
				call_user_func($callback, $page);
			}
		}

		// parse frog tags
		frog_tags_main($page);

		// after all prevent execution of $page->_executeLayout() (the old way was: $page->layout_id = -1)
		exit(0);
	}

	/**
	 * Gets the layout of the current page. If PHP is allowed the PHP content
	 * will be evaluated.
	 */
	public static function get_page_layout($page) {
		if (ALLOW_PHP) {
			ob_start();
			$page->_executeLayout();
			$content = ob_get_contents();
			ob_end_clean();
		}
		else {
			global $__FROG_CONN__;

			$query = 'SELECT content_type, content FROM '.TABLE_PREFIX.'layout WHERE id = ?';

			$statement = $__FROG_CONN__->prepare($query);
			$statement->execute(array(self::get_page_layout_id($page)));

			if ($layout = $statement->fetchObject()) {
				// if content-type not set, we set html as default
				if ($layout->content_type == '')
					$layout->content_type = 'text/html';

				header('Content-Type: '.$layout->content_type.'; charset=UTF-8');
				$content = $layout->content;
			}
			else {
				$content = '';
			}
		}
		return $content;
	}

	/**
	 * Gets the page's layout id as the original method of class Page is
	 * private.
	 */
	public static function get_page_layout_id($page) {
		if ($page->layout_id)
			return $page->layout_id;
		else if ($page->parent)
			return self::get_page_layout_id($page->parent);
		else
			exit ('You need to set a layout!');
	}

	/**
	 * Gets the plain content of the page part and not the already filtered
	 * content. Returns a stdClass object with the members 'content' and
	 * 'filter_id'. If PHP is allowed the PHP content will be evaluated.
	 */
	public static function get_page_content($page, $part, $inherit) {
		global $__FROG_CONN__;
		$query = 'SELECT content, filter_id FROM '.TABLE_PREFIX.'page_part WHERE page_id=? AND name=?';
		if ($statement = $__FROG_CONN__->prepare($query)) {
			do {
				$statement->execute(array($page->id, $part));
				if ($content = $statement->fetchObject()) {
					$content->content = self::execute($content->content, $page);
					return $content;
				}
				else {
					$page = $page->parent;
				}
			}
			while ($inherit && $page);
		}
	}

	/**
	 * Gets the plain content of the snippet and not the already filtered
	 * content. Returns a stdClass object with the members 'content' and
	 * 'filter_id'. If PHP is allowed the PHP content will be evaluated.
	 */
	public static function get_snippet($snippet, $page) {
		global $__FROG_CONN__;
		$query = 'SELECT content, filter_id FROM '.TABLE_PREFIX.'snippet WHERE name=?';
		if ($statement = $__FROG_CONN__->prepare($query)) {
			$statement->execute(array($snippet));
			if ($snippet = $statement->fetchObject()) {
				$snippet->content = self::execute($snippet->content, $page);
				return $snippet;
			}
		}
	}

	/**
	 * Evaluates the PHP code in $content within the context of $page.
	 */
	public static function execute($content, $page) {
		if (ALLOW_PHP) {
			// The PHP content is evaluted by Page::content(). So it is
			// possible to access $page within the content using $this as
			// common in Frog CMS.
			$copy = clone $page;
			$copy->part->body->content_html = $content;
			$content = $copy->content();
		}
		return $content;
	}

	/**
	 * Hack for the page not found plugin.
	 */
	public static function page_not_found_hack() {

		// only throw exception if main page has been found
		if(defined('page_found') && page_found ==  true) {
			throw new Exception('Page not found!');
		}

		// call other observer methods first
		$observerList = Observer::getObserverList('page_not_found');
		unset($observerList['behavior_page_not_found']);
		$alreadyCalled = true;
		foreach($observerList as $callback) {
			if ($callback == 'FrogTagsHacks::page_not_found_hack')
				$alreadyCalled = false;
			elseif (!$alreadyCalled)
				call_user_func($callback);
		}

		if (function_exists('behavior_page_not_found')) {
			global $__FROG_CONN__;

			$query = 'SELECT slug FROM '.TABLE_PREFIX."page WHERE behavior_id='page_not_found'";
			$statement = $__FROG_CONN__->prepare($query);
			$statement->execute();

			if ($page = $statement->fetchObject()) {
				$page = find_page_by_uri($page->slug);
				
				if (is_object($page)) {
					header("HTTP/1.0 404 Not Found");
					header("Status: 404 Not Found");

					frog_tags_main($page);
					exit();
				}
			}
		}
	}

}

?>
