<?php

class Minify
{
	/**
	 * Holds the static instance of the minify object
	 * @var Minify
	 */
	protected static $instance = null;

	/**
	 * Used in the css minifier section
	 * @var boolean
	 */
	protected $in_hack = false;

	/**
	 * Returns a static instance of the Minify object
	 * @return Minify
	 */
	public static function instance()
	{
		if (null === self::$instance)
		{
			self::$instance = new Minify();
		}

		return self::$instance;
	}

	public function run($type, $page)
	{
		$key = md5($type . '-' . $page);
		$rval = Yii::app()->fileCache->get($key);

		if (false !== $rval)
		{
			return $rval;
		}

		$page_files = isset(Yii::app()->params[$type]['page'][$page]) ? Yii::app()->params[$type]['page'][$page] : array();
		$global_files = array();

		if (false === Yii::app()->request->getParam('skip_global', false))
		{
			$global_files = Yii::app()->params[$type]['global'];
		}

		$files = array_merge($global_files, $page_files);
		$browser = Yii::app()->request->getBrowser();
		$rval = '';

		$dependencies = array(
			new CFileCacheDependency(Yii::app()->basePath . DS . 'config' . DS . 'js.php'),
			new CFileCacheDependency(Yii::app()->basePath . DS . 'config' . DS . 'css.php')
		);

		foreach ($files as $file)
		{
			if (true === isset($file['browser']))
			{
				if ($browser['name'] != $file['browser']['name'])
				{
					continue;
				}

				if (true === isset($file['browser']['version']))
				{
					if ($browser['version'] != $file['browser']['version'])
					{
						continue;
					}
				}
			}

			if (true === isset($file['request']))
			{
				$should_continue = false;

				foreach ($file['request'] as $key => $value)
				{
					if ($value != Yii::app()->request->getParam($key, null))
					{
						$should_continue = true;
						break;
					}
				}

				if (true === $should_continue)
				{
					continue;
				}
			}

			if (false === isset($file['combine_only']))
			{
				$file['combine_only'] = false;
			}

			if (false === isset($file['php']))
			{
				$file['php'] = false;
			}

			$file['file'] = Yii::app()->params['docRoot'] . DS . trim($file['file']);
			if (true === file_exists($file['file']))
			{
				if (true === $file['php'])
				{
					ob_start();
					require($file['file']);
					$str = ob_get_contents();
					ob_end_clean();
				}

				else
				{
					$str = file_get_contents($file['file']);
				}

				$rval .= ($file['combine_only'] ? $str : $this->$type($str));

				if (';' != substr($rval, -1) && 'js' == $type)
				{
					$rval .= ';';
				}

				$rval .= "\n";
			}

			else
			{
				throw new Exception('File not found: ' . $file['file']);
			}

			$dependencies[] = new CFileCacheDependency($file['file']);
		}

		Yii::app()->fileCache->set($key, $rval, 0, new CChainedCacheDependency($dependencies));
		return $rval;
	}

	protected function js($js)
	{
		defined('TOKEN_END') || define('TOKEN_END', 1);
		defined('TOKEN_NUMBER') || define('TOKEN_NUMBER', 2);
		defined('TOKEN_IDENTIFIER') || define('TOKEN_IDENTIFIER', 3);
		defined('TOKEN_STRING') || define('TOKEN_STRING', 4);
		defined('TOKEN_REGEXP') || define('TOKEN_REGEXP', 5);
		defined('TOKEN_NEWLINE') || define('TOKEN_NEWLINE', 6);
		defined('TOKEN_CONDCOMMENT_START') || define('TOKEN_CONDCOMMENT_START', 7);
		defined('TOKEN_CONDCOMMENT_END') || define('TOKEN_CONDCOMMENT_END', 8);
		defined('JS_SCRIPT') || define('JS_SCRIPT', 100);
		defined('JS_BLOCK') || define('JS_BLOCK', 101);
		defined('JS_LABEL') || define('JS_LABEL', 102);
		defined('JS_FOR_IN') || define('JS_FOR_IN', 103);
		defined('JS_CALL') || define('JS_CALL', 104);
		defined('JS_NEW_WITH_ARGS') || define('JS_NEW_WITH_ARGS', 105);
		defined('JS_INDEX') || define('JS_INDEX', 106);
		defined('JS_ARRAY_INIT') || define('JS_ARRAY_INIT', 107);
		defined('JS_OBJECT_INIT') || define('JS_OBJECT_INIT', 108);
		defined('JS_PROPERTY_INIT') || define('JS_PROPERTY_INIT', 109);
		defined('JS_GETTER') || define('JS_GETTER', 110);
		defined('JS_SETTER') || define('JS_SETTER', 111);
		defined('JS_GROUP') || define('JS_GROUP', 112);
		defined('JS_LIST') || define('JS_LIST', 113);
		defined('DECLARED_FORM') || define('DECLARED_FORM', 0);
		defined('EXPRESSED_FORM') || define('EXPRESSED_FORM', 1);
		defined('EXPRESSED_FORM') || define('EXPRESSED_FORM', 2);

		return JSMinPlus::minify($js);
	}

	protected function css($css)
	{
	        $css = str_replace("\r\n", "\n", $css);

	        // preserve empty comment after '>'
	        // http://www.webdevout.net/css-hacks#in_css-selectors
	        $css = preg_replace('@>/\\*\\s*\\*/@', '>/*keep*/', $css);

	        // preserve empty comment between property and value
	        // http://css-discuss.incutio.com/?page=BoxModelHack
	        $css = preg_replace('@/\\*\\s*\\*/\\s*:@', '/*keep*/:', $css);
	        $css = preg_replace('@:\\s*/\\*\\s*\\*/@', ':/*keep*/', $css);

	        // apply callback to all valid comments (and strip out surrounding ws
	        $css = preg_replace_callback('@\\s*/\\*([\\s\\S]*?)\\*/\\s*@', array($this, 'commentCallback'), $css);

	        // remove ws around { } and last semicolon in declaration block
	        $css = preg_replace('/\\s*{\\s*/', '{', $css);
	        $css = preg_replace('/;?\\s*}\\s*/', '}', $css);

	        // remove ws surrounding semicolons
	        $css = preg_replace('/\\s*;\\s*/', ';', $css);

	        // remove ws around urls
	        $css = preg_replace('/
	                url\\(      # url(
	                \\s*
	                ([^\\)]+?)  # 1 = the URL (really just a bunch of non right parenthesis)
	                \\s*
	                \\)         # )
	            /x', 'url($1)', $css
	        );

	        // remove ws between rules and colons
	        $css = preg_replace('/
	                \\s*
	                ([{;])              # 1 = beginning of block or rule separator
	                \\s*
	                ([\\*_]?[\\w\\-]+)  # 2 = property (and maybe IE filter)
	                \\s*
	                :
	                \\s*
	                (\\b|[#\'"-])        # 3 = first character of a value
	            /x', '$1$2:$3', $css
	        );

	        // remove ws in selectors
	        $css = preg_replace_callback('/
	                (?:              # non-capture
	                    \\s*
	                    [^~>+,\\s]+  # selector part
	                    \\s*
	                    [,>+~]       # combinators
	                )+
	                \\s*
	                [^~>+,\\s]+      # selector part
	                {                # open declaration block
	            /x'
	            ,array($this, 'selectorsCallback'), $css
			);

	        // minimize hex colors
	        $css = preg_replace('/([^=])#([a-f\\d])\\2([a-f\\d])\\3([a-f\\d])\\4([\\s;\\}])/i', '$1#$2$3$4$5', $css);

	        // remove spaces between font families
	        $css = preg_replace_callback('/font-family:([^;}]+)([;}])/', array($this, 'fontFamilyCallback'), $css);

	        $css = preg_replace('/@import\\s+url/', '@import url', $css);

	        // replace any ws involving newlines with a single newline
	        $css = preg_replace('/[ \\t]*\\n+\\s*/', "\n", $css);

	        // separate common descendent selectors w/ newlines (to limit line lengths)
	        $css = preg_replace('/([\\w#\\.\\*]+)\\s+([\\w#\\.\\*]+){/', "$1\n$2{", $css);

	        // Use newline after 1st numeric value (to limit line lengths).
	        $css = preg_replace('/
	            ((?:padding|margin|border|outline):\\d+(?:px|em)?) # 1 = prop : 1st numeric value
	            \\s+
	            /x'
	            ,"$1\n", $css
			);

	        // prevent triggering IE6 bug: http://www.crankygeek.com/ie6pebug/
	        $css = preg_replace('/:first-l(etter|ine)\\{/', ':first-l$1 {', $css);

	        return trim($css);
	}

	/**
	  * Replace what looks like a set of selectors
	  *
	  * @param array $regex_matches regex matches
	  *
	  * @return string
	  */
	protected function selectorsCallback(array $regex_matches)
	{
		// remove ws around the combinators
		return preg_replace('/\\s*([,>+~])\\s*/', '$1', $regex_matches[0]);
	}

    /**
     * Process a comment and return a replacement
     *
     * @param array $regex_matches regex matches
     *
     * @return string
     */
    protected function commentCallback(array $regex_matches)
    {
        $has_surrounding_ws = (trim($regex_matches[0]) !== $regex_matches[1]);
        $regex_matches = $regex_matches[1];

        // $regex_matches is the comment content w/o the surrounding tokens,
        // but the return value will replace the entire comment.
        if ($regex_matches === 'keep')
        {
            return '/**/';
        }

        if ($regex_matches === '" "')
        {
            // component of http://tantek.com/CSS/Examples/midpass.html
            return '/*" "*/';
        }

        if (preg_match('@";\\}\\s*\\}/\\*\\s+@', $regex_matches))
        {
            // component of http://tantek.com/CSS/Examples/midpass.html
            return '/*";}}/* */';
        }

        if ($this->in_hack)
        {
            // inversion: feeding only to one browser
            if (preg_match('@
                    ^/               # comment started like /*/
                    \\s*
                    (\\S[\\s\\S]+?)  # has at least some non-ws content
                    \\s*
                    /\\*             # ends like /*/ or /**/
                @x', $regex_matches, $n))
            {
                // end hack mode after this comment, but preserve the hack and comment content
                $this->in_hack = false;
                return "/*/{$n[1]}/**/";
            }
        }

        // comment ends like \*/
        if (substr($regex_matches, -1) === '\\')
        {
            // begin hack mode and preserve hack
            $this->in_hack = true;
            return '/*\\*/';
        }

        // comment looks like /*/ foo */
        if ($regex_matches !== '' && $regex_matches[0] === '/')
        {
            // begin hack mode and preserve hack
            $this->in_hack = true;
            return '/*/*/';
        }

        if ($this->in_hack)
        {
            // a regular comment ends hack mode but should be preserved
            $this->in_hack = false;
            return '/**/';
        }

        // Issue 107: if there's any surrounding whitespace, it may be important, so
        // replace the comment with a single space
        return $has_surrounding_ws // remove all other comments
            ? ' '
            : '';
    }

    /**
     * Process a font-family listing and return a replacement
     *
     * @param array $regex_matches regex matches
     *
     * @return string
     */
    protected function fontFamilyCallback(array $regex_matches)
    {
        $regex_matches[1] = preg_replace('/
                \\s*
                (
                    "[^"]+"      # 1 = family in double qutoes
                    |\'[^\']+\'  # or 1 = family in single quotes
                    |[\\w\\-]+   # or 1 = unquoted family
                )
                \\s*
            /x', '$1', $regex_matches[1]);

        return 'font-family:' . $regex_matches[1] . $regex_matches[2];
    }
}

?>