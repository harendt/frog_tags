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
		Puts out the breadcrumb of the current page
		@usage <f:breadcrumb /> @endusage
	*/
	public function tag_breadcrumb() {
		return $this->page->breadcrumb();
	}

	/*
		Renders a link to the current page or to the page specified in the
		argument @url@. HTML attributes like @class="..."@ will be included in
		the rendered link.

		@arg url Specifies the page to which a link should be rendered. If
		empty the current page will be used.

		@usage
			<f:link [url="..."] [HTML attributes] />
			<f:link [url="..."] [HTML attributes]>Link-Text</f:link>
		@endusage
	*/
	public function tag_link() {
		// change page if url is specified
		$url = $this->get_argument('url');
		if (!empty($url)) {
			$found = $this->page->find($url);
			if ($found)
				$this->page = $found;
		}
		unset($this->args['url']);

		// include HTML attributes
		$options = '';
		foreach($this->args as $arg => $value) {
			$options .= " $arg=\"$value\"";
		}
		$options = trim($options);

		// render content if any
		if (empty($this->content))
			$label = NULL;
		else
			$label = $this->expand();

		// render link
		return $this->page->link($label, $options);
	}

	/*
		Puts out the breadcrumbs for the current page.
		@arg separator Character or string that separates breadcrumbs. Default
		is "@ &gt; @".
		@usage <f:breadcrumbs [separator="separator_string"] /> @endusage
	*/
	public function tag_breadcrumbs() {
		$separator = $this->get_argument('separator', ' &gt; ');
		$breadcrumbs = '';
		$page = $this->page;
		while ($page = $page->parent) {
			if ($page->breadcrumb() != '') {
				$breadcrumbs = $page->link($page->breadcrumb(), 'class="breadcrumb"') . '<span class="breadcrumb-separator">' . $separator . '</span>' . $breadcrumbs;
			}
		}
		$breadcrumbs .= '<span class="breadcrumb">' . $this->page->breadcrumb() . '</span>';
		return $breadcrumbs;
	}

	/*
		Renders the containing elements if all of the listed page parts exist.

		@arg part A list separated by spaces of page parts. Default is @body@.
		@arg inherit If true, the tag will search ancestors for each part
		aswell. Default is @false@.
		@arg find When listing more than one part, you may set this argument to
		@any@ so that this tag will render the containing elements if any (not
		all as by default) of the listed parts exists.

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
	}

	/*
		The opposite of the @f:if_content@ tag.
	*/
	public function tag_unless_content() {
		return $this->tag_if_content(true);
	}

	/*
		Renders the content of the current page.
		@arg part Specifies which page part should be rendered. Default is
		@body@.
		@arg inherit Specifies that if a page does not have the specified page
		part the tag should render the parent's page part. Default is @false@.
		@usage <f:content [part="page_part"] [inherit="true|false"] /> @endusage
	*/
	public function tag_content() {
		$part    = $this->get_argument('part', 'body');
		$inherit = $this->get_argument('inherit') == 'true' ? true : false;

		// get stdClass object width members 'content' and 'filter_id'
		$content = FrogTagsHacks::get_page_content($this->page, $part, $inherit);

		return $this->parse($content->content, $this, $content->filter_id);
	}

	/*
		Renders the specified snippet.
		@arg name The name of the snippet that should be rendered.
		@usage <f:snippet name="snippet_name" /> @endusage
	*/
	public function tag_snippet() {
		$name = $this->require_argument('name');

		// get a stdClass object with members 'content' and 'filer_id'
		$snippet = FrogTagsHacks::get_snippet($name, $this->page);

		return $this->parse($snippet->content, $this, $snippet->filter_id);
	}

	/*
		Renders the content of the specified file.

		@arg file Specifies the file that should be included. The path must be
		given relative to the public directory. Only files in the public
		directory are allowed.
		@usage <f:include file="filename" /> @endusage
	*/
	public function tag_include() {
		$filename = $this->require_argument('file');
		$public   = realpath(FROG_ROOT.'/public/');
		$filename = realpath($public.'/'.$filename);
		if (substr_compare($public, $filename, 0, strlen($public)) != 0)
			throw new Exception('Only files in the public directory are allowed to be included!');
		$content = file_get_contents($filename);
		$content = FrogTagsHacks::execute($content, $this->page);
/*		if (ALLOW_PHP) {
			ob_start();
			eval('?>'.$content);
			$string = ob_get_contents();
			ob_end_clean();
		}*/
		$content = $this->parse($content, $this);
		return $content;
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

	/*
		Inside this tag all page related tags refer to the page found by the @url@ argument.

		@usage <f:find url="...">...</f:find> @endusage
	*/
	public function tag_find() {
		$url = $this->require_argument('url');
		$found = $this->page->find($url);
		if ($found) {
			$this->page = $found;
			return $this->expand();
		}
	}

	/*
		Renders the containing elements if the current page's url matches the
		specified pattern.
		@arg match A regular expression that is tested to match the current
		url. Leading and trailing delimiters can be omitted.
		@arg flags Pattern modifiers. See <a href="http://www.php.net/manual/reference.pcre.pattern.modifiers.php">php.net</a>
		for further details about pattern modifiers.
		@usage <f:if_url match="pattern" [flags="..."]>...</f:if_url> @endusage
		@see f:unless_url
	*/
	public function tag_if_url($invert = false) {
		$pattern = $this->require_argument('match');
		$flags = $this->get_argument('flags');
		$url = '/' . $this->page->url;
		$render = 1 == preg_match("@$pattern@$flags", $url);
		if ($invert)
			$render = !$render;
		if ($render)
			return $this->expand();
	}

	/*
		The opposite of the @f:if_url@ tag.
	*/
	public function tag_unless_url() {
		return $this->tag_if_url(true);
	}

	/*
		Tag to iterate over a collection tag, e.g. @f:children@.
		@arg collection Specifies the collection tag name (optional).
		@usage <f:children><f:each [collection="children"]>...</f:each></f:children> @endusage
		@see f:children
	*/
	public function tag_each() {
		$parent = $this->get_argument('collection', NULL);
		$collection = $this->require_class_attribute('collection', $parent);
		$result = '';
		foreach($collection as $item) {
			$attributes = array('content', 'page'); // default attributes
			if(isset($parent->collection_attributes)) {
				$attributes = array_merge($attributes, $parent->collection_attributes);
			}
			foreach($attributes as $attribute) {
				if (isset($item->$attribute)) {
					$this->$attribute = $item->$attribute;
					unset($item->$attribute);
				}
			}
			$result .= $this->expand();
		}
		return $result;
	}

	/*
		Collects all children for the current page.

		@arg order If @oder@ is @desc@ the children will be ordered descending.
		Otherwise they will be ordered ascending.
		@arg by Specifies how the children should be ordered. Default is
		@position@.
		@arg offset Works only if @limit@ is specified aswell.
		@arg limit Limits the number of children.
		@arg status If @status@ is @all@ also hidden pages will be included.

		@usage
		> <f:children
		>   [order="asc|desc"]
		>   [by="position|created|published|updated"]
		>   [offset="..."]
		>   [limit="..."]
		>   [status="all"]>
		>   ...
		> </f:children>
		@endusage

		@see f:each
	*/
	public function tag_children() {
		$order = $this->get_argument('order');
		if(!in_array($order, array('desc', 'asc')))
			$order = 'asc';
		$order = strtoupper($order);

		$orderBy = $this->get_argument('by');
		if(!in_array($orderBy, array('position', 'created', 'published', 'updated')))
			$orderBy = 'position';
		if(in_array($orderBy, array('created', 'published', 'updated')))
			$orderBy .= '_on';

		$offset = $this->get_argument('offset', 0);
		$offset = intval($offset);

		$limit = $this->get_argument('limit', 0);
		$limit = intval($limit);

		$status = $this->get_argument('status');
		if ($status == 'all')
			$include_hidden = true;
		else
			$include_hidden = false;

		$args = array('order' => "page.$orderBy $order", 'offset' => $offset, 'limit' => $limit);
		$children = $this->page->children($args, array(), $include_hidden);
		if ($limit == 1 && !empty($children))
			$children = array($children);
		$this->collection = array();
		if (is_array($children)) {
			foreach($children as $child) {
				array_push($this->collection, (object) array('page' => $child));
			}
		}
		return $this->expand();
	}

}

?>
