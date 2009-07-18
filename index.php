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

if (!defined('FrogTagsPluginIncluded')) {

	define('FrogTagsPluginIncluded', true);

	Plugin::setInfos(array(
		'id'          => 'frog_tags',
		'title'       => 'Frog Tags',
		'description' => 'Allows defining and using of HTML-like tags - called Frog tags.',
		'version'     => '0.0.1', 
		'author'      => 'Bastian Harendt',
		'website'     => 'http://github.com/harendt/frog_tags/',
		'update_url'  => 'http://github.com/harendt/frog_tags/raw/master/version.xml'
	));

	Plugin::addController('frog_tags', 'Frog Tags', '', false);

	include_once('FrogTagsParser.php');
	include_once('StandardTags.php');

	Observer::observe('page_found', 'parse_frog_tags');

	function parse_frog_tags($page) {
		// get content
		$page->_executeLayout();
		$content = ob_get_contents();
		ob_clean();

		// parse frog tags
		$parser = new FrogTagsParser($page);
		$content = $parser->parse($content);

		// prevent a second execution of $page->_exectuteLayout()
		$page->layout_id = -1;

		echo $content;
	}

}

?>
