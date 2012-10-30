<?php

	/**
	 * Dev class. Not intended to be used on production!
	 * 
	 * @version 1.0
	 * @package Dev
	 * @author Camilo Bravo <cambraca@gmail.com>
	 */
	class CModel_Dev
	{
		private static $_enabled = TRUE;
		private static $toogleDisplay = FALSE;
		
		const DISPLAY_MODE_CLI = 'DISPLAY_MODE_CLI';
		const DISPLAY_MODE_RAW = 'DISPLAY_MODE_RAW';
		const DISPLAY_MODE_SIMPLE = 'DISPLAY_MODE_SIMPLE';
		const DISPLAY_MODE_ADVANCED = 'DISPLAY_MODE_ADVANCED';
		
		const LOG_FILE = '/tmp/pricetag_log';
		
		/**
		 * Write to a log file
		 * @param mixed $hash 
		 */
		public static function l($hash, $name = 'Debug')
		{
			if( !self::$_enabled )
				return;

			$f = fopen(self::LOG_FILE, 'a');
			if (is_object($hash) || is_array($hash))
			{
				fputs($f, date('c').' '.$name.': ');
				ob_start();
				var_dump($hash);
				fputs($f, ob_get_clean());
			}
			else
				fputs($f, date('c').' '.$name.': '.$hash.PHP_EOL);
			fclose($f);
		}

		/**
		 * displays HTML formated print_r calls on the hash variable, naming the section
		 * with the name variable if set.
		 *
		 * Only outputs if debugging is enabled
		 *
		 * Simple layout is non JS
		 *
		 * @param mixed $hash
		 * @param string $name
		 * @param boolean $simple if TRUE will not display a collapsable tree
		 */
		public static function d($hash, $name = "Debug Display", $mode = self::DISPLAY_MODE_ADVANCED)
		{
			if( !self::$_enabled )
				return;
			
//			//if it's an Entity, show it differently
//			if (is_object($hash) && $hash instanceof CModel_Entity)
//				$hash = array();

			// print the javascript function toggleDisplay() and then the transformed output
			if ($mode != self::DISPLAY_MODE_RAW && $mode != self::DISPLAY_MODE_CLI && !self::$toogleDisplay)
			{
				echo '<script language="Javascript">function toggleDisplay(id) { document.getElementById(id).style.display = (document.getElementById(id).style.display == "inline") ? "none" : "inline"; }</script>';
				self::$toogleDisplay = TRUE;
			}

			switch ($mode)
			{
				case self::DISPLAY_MODE_CLI:
					echo "****************** START: $name ******************\n";
					echo print_r($hash, TRUE);
					echo "\n****************** END: $name ******************\n";
					break;
				case self::DISPLAY_MODE_RAW:
					echo "<div style='font-size: 12px; align: left; text-align: left;'>";
					echo "<pre>";
					echo "<p><b>$name</b></p>";
					echo htmlentities(print_r($hash, TRUE));
					break;
				case self::DISPLAY_MODE_SIMPLE:
					$token = substr(md5(rand().'\\0'), 0, 7);
					echo "<div style='font-size: 12px; align: left; text-align: left;'>";
					echo "<pre>";
					echo "<p><b><a href=\"javascript:toggleDisplay('{$token}');\">{$name}</a></b></p>";
					echo "<div id=\"{$token}\" style=\"display: none;\">";
					echo htmlentities(print_r($hash, TRUE));
					echo '</div>';
					break;
				case self::DISPLAY_MODE_ADVANCED:
					set_time_limit(60);

					echo "<div style='font-size: 12px; align: left; text-align: left;'>";
					echo "<pre>";
					echo "<p><b>$name</b></p>";
					$out = print_r($hash, TRUE);

					//sql objects
					$out = preg_replace(
						'/(SQL) (Object\n[ \t]*\(\n[ \t]*\[sql\:private\] \=\>[^\w]*(SELECT|select)[^\w]*[^F]*(FROM|from)[^\w]*(\w*))/',
						"\\1 [\\3 - \\5] \\2",
						$out
					);
					$out = preg_replace(
						'/(SQL) (Object\n[ \t]*\(\n[ \t]*\[sql\:private\] \=\>[^\w]*(INSERT INTO|REPLACE INTO|\w*)[^\w]*(\w*))/ie',
						"strtolower('\\3') == 'insert into' || strtolower('\\3') == 'replace into' ? '\\1 ['.substr('\\3',0,strpos('\\3',' ')).' - \\4] \\2'
						: (
							strtolower('\\3') == 'call'
								|| strtolower('\\3') == 'update'
							? '\\1 [\\3 - \\4] \\2'
							: '\\1 [\\3] \\2'
						)",
						$out
					);

					//remove line breaks inside serialized arrays
					$count = 1;
					$out_lines = explode("\n", $out);
					$out = ''; $must_join = FALSE;
					foreach ($out_lines as $line)
					{
						if ($must_join)
						{
							$out = $out." ".$line;
							$line = $old_line.' '.$line;
						}
						else
						{
							if ($out)
								$out = $out."\n".$line;
							else
								$out = $line;
						}

						//is this a line that has a serialized array?
						if (strpos($line, '=> a:') !== FALSE)
						{
							//count the "s
							preg_match_all('/[^\\\]\"/', str_replace(':""', ':', $line), $matches, PREG_OFFSET_CAPTURE);
							if (count($matches[0]) % 2)
							{
								$must_join = TRUE;
								$old_line = $line;
							}
							else
								$must_join = FALSE;
						} else $must_join = FALSE;
					}

					//unserialize arrays
					$out = preg_replace(
						'/([ ]*)(\[[0-9a-zA-Z\_ ]+\] \=\> )(a\:[0-9]+\:\{.*\})(\n)/Ue',
						"'\\1\\2\\3\n\\1    Serialized '.self::addPrefixToEachLine(print_r(unserialize(stripslashes('\\3')), TRUE), '    \\1').'\\4'",
						$out
					);

					//parse xml
					$out = preg_replace(
						'/([ ]*)(\[[0-9a-zA-Z\_ ]+\] \=\> )(<\?xml[^>\?]*\?>\s<(\w+)>)(.*)(<\/\\4>)/seU',
						"'\\1\\2XML String\n\\1(\n\\1        '.self::addPrefixToEachLine(self::formatXmlString(stripslashes('\\3\\5\\6')), '        \\1').'\\1)'",
						$out);

					$out = htmlentities($out);

					$out = str_replace(
						array('XML*', '*XML', 'XML-', '-XML'),
						array('<strong>', '</strong>', '<font color=red>', '</font>'),
						$out
					);

					$not_first = FALSE; $id = '';
					$out = preg_replace(
						'/(Serialized Array|Object|Array|XML String)\n[ \t]*\(/Ue',
						"(\$not_first ? '<a href=\"javascript:toggleDisplay(\''.(\$id = substr(md5(rand().'\\0'), 0, 7)).'\');\">\\1</a>' : '\\1').'<div id=\"'.\$id.'\" style=\"display: '.(!\$not_first ? (\$not_first = 'inline') : 'none').';\">'",
						$out
					);

					// replace ')' on its own on a new line (surrounded by whitespace is ok) with '</div>'
					$out = preg_replace('/^\s*\)$/m', '</div>', $out);
					$out = str_replace("\n\n", "\n", $out);

					echo $out;
					break;
				default:
			}

			if($mode != self::DISPLAY_MODE_CLI)
			{
				echo "</pre>";
				echo "</div>";
			}
		}
		
		/**
		 * JS / HTML processor for colapsable arrays
		 * @param string $txt
		 * @param string $prefix
		 * @return string
		 */
		private static function addPrefixToEachLine($txt, $prefix)
		{
			return preg_replace('/(\n)(.+)/Ue', "'\\1'.\$prefix.'\\2'", $txt);
		}

		/**
		 * Processor for strings that contain XML
		 */
		private static function formatXmlString($xml) {
			// add marker linefeeds to aid the pretty-tokeniser (adds a linefeed between all tag-end boundaries)
			$xml = preg_replace('/(>)(\s)*(<\/*)/', "$1\n$3", $xml);

			// now indent the tags
			$token      = strtok($xml, "\n");
			$result     = ''; // holds formatted version as it is built
			$pad        = 0; // initial indent
			$matches    = array(); // returns from preg_matches()

			// scan each line and adjust indent based on opening/closing tags
			while ($token !== false) :

				// test for the various tag states

				// 1. open and closing tags on same line - no change
				if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) :
				$indent=0;
				// 2. closing tag - outdent now
				elseif (preg_match('/^<\/\w/', $token, $matches)) :
				$pad-=4;
				$indent = 0;
				// 3. opening tag - don't pad this one, only subsequent tags
				elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) :
				$indent=4;
				// 4. no indentation needed
				else :
				$indent = 0;
				endif;

				// pad the line with the required number of leading spaces
				$line    = str_pad($token, strlen($token)+$pad, ' ', STR_PAD_LEFT);
				$result .= $line . "\n"; // add to the cumulative result, with linefeed
				$token   = strtok("\n"); // get the next token
				$pad    += $indent; // update the pad size for subsequent lines
			endwhile;

			$result = preg_replace('/(<(\w+)[^>]*>)([^<]*)(<\/\\2>)/', '\\1XML-\\3-XML\\4', $result);
			$result = preg_replace('/(<\/?)(\w+)/', '\\1XML*\\2*XML', $result);

			return $result;
		}

	}