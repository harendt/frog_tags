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

if (!defined('FrogTagsControllerIncluded')) {

	define('FrogTagsControllerIncluded', true);

	include_once('FrogTagsDocumentation.php');

	class FrogTagsController extends PluginController {

		public function __construct() {
			AuthUser::load();
			if (!AuthUser::isLoggedIn())
				redirect(get_url('login'));
			$this->setLayout('backend');
			$this->assignToLayout('sidebar', new View('../../plugins/frog_tags/views/sidebar'));
		}
		
		public function documentation() {
			$this->display('frog_tags/views/documentation');
		}

		public function available_tags() {
			$this->display('frog_tags/views/available_tags');
		}

		public function error_page($title, $message) {
			$this->setLayout(false);
			$this->display('frog_tags/views/error_page', array('title' => $title, 'message' => $message), true);
		}
	}

}

?>
