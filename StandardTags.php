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

class StandardTags extends FrogTags {

	/*
		Puts out the title of the current page
		@usage <f:title /> @endusage
	*/
	public function tag_title() {
		return $this->page->title();
	}

	/*
		Puts out the breadcrumbs for the current page.
		@arg separator Character or string that separates breadcrumbs. Default is @&gt;@.
		@usage <f:breadcrumbs [separator="separator_string"] /> @endusage
	*/
	public function tag_breadcrumbs() {
		$separator = $this->get_argument('separator', '&gt;');
		return $this->page->breadcrumbs($separator);
	}

	/*
		Renders the containing elements if all of the listed page parts exist.
		@arg part     A list separated by spaces of page parts. Default is @body@.
		@arg inherit  If true, the tag will search ancestors for each part aswell. Default is @false@.
		@arg find     When listing more than one part, you may set this argument to @any@ so that this tag will render the containing elements if any (not all as by default) of the listed parts exists.
		@see f:unless_content
		@usage
			> <f:if_content [part="part_name other_part"] [inherit="true|false"]>
			>   ...
			> </f:if_content>
		@endusage
	*/
	public function tag_if_content($invert = false) {
		$parts   = $this->get_argument('part', 'body');
		$inherit = $this->get_argument('inherit') == 'true' ? true : false;
		$any     = $this->get_argument('find') == 'any' ? true : false;

		$parts = preg_split('/\s+/', $parts, -1, PREG_SPLIT_NO_EMPTY);
		foreach($parts as $part) {
			if ($this->page->hasContent($part, $inherit)) {
				$render = true;
				if ($any) break;
			}
			else {
				$render = false;
				if (!$any) break;
			}
		}

		if ($invert)
			$render = !$render;
		if ($render)
			return $this->expand();
		else
			return '';
	}

	/*
		The opposite of the @f:if_content@ tag.
	*/
	public function tag_unless_content() {
		return $this->tag_if_content(true);
	}

	/*
		Renders the content of the current page.
		@arg part     Specifies which page part should be rendered. Default is @body@.
		@arg inherit  Specifies that if a page does not have the specified page part the tag should render the parent's page part. Default is @false@.
		@usage <f:content [part="page_part"] [inherit="true|false"] /> @endusage
	*/
	public function tag_content() {
		$part    = $this->get_argument('part', 'body');
		$inherit = $this->get_argument('inherit') == 'true' ? true : false;
		$content = $this->page->content($part, $inherit);
		return $this->parse($content);
	}

	/*
		Renders the specified snippet.
		@arg snippet The name of the snippet that should be rendered.
		@usage <f:snippet name="snippet_name" /> @endusage
	*/
	public function tag_snippet() {
		$name = $this->require_argument('name');
		ob_start();
		$this->page->includeSnippet($name);
		$snippet = ob_get_contents();
		ob_end_clean();
		return $this->parse($snippet);
	}

	/*
		Puts out the url of the public directory.
		@usage <f:public_url /> @endusage
	*/
	public function tag_public_url() {
		return URL_PUBLIC . (endsWith(URL_PUBLIC, '/') ? '': '/') . 'public/';
	}

	/*
		Puts out the base url of this website.
		@usage <f:base_url /> @endusage
	*/
	public function tag_base_url() {
		return BASE_URL;
	}

}

?>
