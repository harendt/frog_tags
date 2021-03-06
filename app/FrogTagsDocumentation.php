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

class FrogTagBrief {

	public $name, $brief, $arguments, $usage;

	/**
	 * Puts out a HTML-formatted tag brief.
	 */
	public function to_html() {
		$html = '<h2 id="tag_' . $this->name . '">' . $this->name . '</h2>';
		if ($this->brief != '') {
			$html .= '<h3>Brief:</h3>';
			$brief = $this->filter($this->brief);
			$html .= '<p>' . str_replace("\n", '</p><p>', $brief) . '</p>';
		}
		if (count($this->arguments) > 0) {
			$html .= '<h3>Arguments:</h3>';
			$html .= '<dl>';
			foreach($this->arguments as $argument => $description) {
				$description = $this->filter($description);
				$html .= "<dt>$argument</dt><dd>$description</dd>";
			}
			$html .= '</dl>';
		}
		if ($this->see != '') {
			$html .= '<h3>See also:</h3>';
			$html .= '<p>' . $this->filter($this->see) . '</p>';
		}
		if ($this->usage != '') {
			$html .= '<h3>Usage:</h3>';
			$html .= '<pre>' . htmlspecialchars($this->usage) . '</pre>';
		}
		return $html;
	}

	/**
	 * Creates a new tag brief for the tag specified in $name. The parameter
	 * $brief is parsed for the keywords @arg, @usage and @endusage. The
	 * remaining is treated as brief.
	 */
	public function __construct($name, $brief) {
		// name
		$this->name = $name;

		// preprocessing
		$brief = $this->trim_lines($brief);
		foreach(array('arg', 'usage', 'see') as $keyword) {
			$brief = str_replace("@$keyword", "\n\n@$keyword", $brief);
		}

		// arguments (match the pattern @arg <name> <description>)
		$this->arguments = array();
		$brief = preg_replace_callback("/@arg\s+(\w+)\s+(.*?)(\n\n|$)/s", array($this, 'replace_arg_pattern'), $brief);

		// see also (match the pattern @see ...)
		$this->see = '';
		$brief = preg_replace_callback("/@see\s+(.*?)(\n\n|$)/s", array($this, 'replace_see_pattern'), $brief);

		// usage (match the pattern @usage ... @endusage)
		$this->usage = '';
		$brief = preg_replace_callback("/@usage(.*?)@endusage/s", array($this, 'replace_usage_pattern'), $brief);
		$this->usage = $this->trim_usage();

		// brief (the remaining content)
		$this->brief = $brief;
		$this->brief = $this->trim_brief();
	}

	private function replace_arg_pattern($match) {
		$argument = $match[1];
		$description = trim($match[2]);
		$description = preg_replace('/\s+/', ' ', $description);
		$this->arguments[$argument] = $description;
		return '';
	}

	private function replace_see_pattern($match) {
		$see = trim($match[1]);
		$see = preg_replace('/\s+/', ' ', $see);
		if ($see != '')
			$this->see .= ($this->see!=''?' ':'') . $see;
		return '';
	}

	private function replace_usage_pattern($match) {
		if ($this->usage != '') $this->usage .= "\n\n";
		$this->usage .= trim($match[1]);
		return '';
	}

	private function trim_lines($string) {
		$lines = explode("\n", trim($string));
		foreach($lines as &$line) {
			$line = trim($line);
		}
		return implode("\n", $lines);
	}

	private function trim_usage() {
		$usage = $this->trim_lines($this->usage);
		$usage = preg_replace('/^> ?/m', '', $usage);
		return $usage;
	}

	private function trim_brief() {
		$brief = $this->trim_lines($this->brief);

		// replace repeated spaces with single ones
		$lines = explode("\n", $brief);
		foreach($lines as &$line) {
			$line = preg_replace("/\s+/", " ", $line);
		}
		$brief = implode("\n", $lines);

		// replace single new lines with spaces
		$brief = preg_replace("/([^\n])\n([^\n])/", "\\1 \\2", $brief);

		// replaces repeated new lines with single ones
		$brief = preg_replace("/\n+/", "\n", $brief);
		return $brief;
	}

	private function filter($string) {
		// treat *...* as bold text
		$string = preg_replace('/\*(.*?)\*/', '<b>$1</b>', $string);
		// treat _..._ as italic text
		$string = preg_replace('/_(.*?)_/', '<i>$1</i>', $string);
		// treat @...@ as code
		$string = preg_replace_callback(
			'/@(.*?)@/',
			create_function(
				'$matches',
				'return "<code>" . htmlspecialchars($matches[1]) . "</code>";'
			),
			$string
		);
		// treat f:... as frog tag and replace it with a link
		$string = preg_replace('/f:(\w+)/', '<a href="'.$_SERVER['REQUEST_URI'].'#tag_$1">$1</a>', $string);
		return $string;
	}

	public static function compare($a, $b) {
		return strcmp($a->name, $b->name);
	}
}

class FrogTagsDocumentation {
	protected $briefNumber, $briefs;

	public function __construct() {
		$this->briefs = array();
		foreach (get_included_files() as $filename)
			$this->parse_source(file_get_contents($filename));
		usort($this->briefs, 'FrogTagBrief::compare');
	}

	/**
	 * Puts out a list of all tag briefs formatted using HTML.
	 */
	public function html() {
		$html = '';
		foreach($this->briefs as $brief)
			$html .= $brief->to_html();
		echo $html;
	}

	/**
	 * Parses a php source file for functions whose names start with "tag_". If
	 * a function declaration has a preceding comment, this comment is treated
	 * as brief for the particular tag.
	 *
	 * This parser is actually quite dumb as it doesn't check whether the
	 * parsed function belongs to a class derived from FrogTags. But it checks
	 * if the function belongs to a tag in the tag list. So there should only
	 * be problems if there is an other function with the same name.
	 */
	protected function parse_source($string) {
		$definedTags = FrogTagsList::get();
		$this->briefnum = 0;
		$string = preg_replace_callback("|/\*(.*)\*/|Us", array($this, 'comment_to_tag'), $string); // preprocessing
		preg_match_all("|<(brief-\d+)>(.*)</\\1>\s+(public\s)?\s*function\s+tag_(\w+)\s*\(.*\)\s*|Us", $string, $matches, PREG_SET_ORDER);
		foreach($matches as $match) {
			$name = $match[4];
			$brief = $match[2];
			if (isset($definedTags[$name]))
				array_push($this->briefs, new FrogTagBrief($name, $brief));
		}
	}

	protected function comment_to_tag($matches) {
		$this->briefNumber++;
		$num = $this->briefNumber;
		$brief = $matches[1];
		return "<brief-$num>$brief</brief-$num>";
	}
}

?>
