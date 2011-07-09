<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test,
    s9e\Toolkit\TextFormatter\JSParserGenerator;

include_once __DIR__ . '/../Test.php';
include_once __DIR__ . '/../../src/TextFormatter/JSParserGenerator.php';

class JSParserGeneratorTest extends Test
{
	protected function encodeArray(array $arr)
	{
		return $this->call(
			's9e\\Toolkit\\TextFormatter\\JSParserGenerator',
			'encodeArray',
			func_get_args()
		);
	}

	protected function encodeConfig(array $config)
	{
		return $this->call(
			's9e\\Toolkit\\TextFormatter\\JSParserGenerator',
			'encodeConfig',
			func_get_args()
		);
	}

	/**
	* @test
	*/
	public function encodeArray_can_encode_arrays_to_objects()
	{
		$arr = array(
			'a' => 1,
			'b' => 2
		);

		$this->assertSame(
			'{a:1,b:2}',
			$this->encodeArray($arr)
		);
	}

	/**
	* @test
	*/
	public function encodeArray_can_encode_arrays_to_Arrays()
	{
		$arr = array(1, 2);

		$this->assertSame(
			'[1,2]',
			$this->encodeArray($arr)
		);
	}

	/**
	* @test
	*/
	public function encodeArray_can_convert_regexp_strings_to_RegExp_objects()
	{
		$arr = array('/foo/');

		$struct = array(
			'isRegexp' => array(
				array(true)
			)
		);

		$this->assertContains(
			'/foo/',
			$this->encodeArray($arr, $struct)
		);
	}


	/**
	* @test
	*/
	public function encodeArray_can_convert_regexp_strings_to_RegExp_objects_with_g_flag()
	{
		$arr = array('/foo/');

		$struct = array(
			'isGlobalRegexp' => array(
				array(true)
			)
		);

		$this->assertContains(
			'/foo/g',
			$this->encodeArray($arr, $struct)
		);
	}

	/**
	* @test
	* @depends encodeArray_can_encode_arrays_to_Arrays
	*/
	public function encode_encodes_booleans_to_0_and_1()
	{
		$this->assertSame(
			'[1,0,1]',
			JSParserGenerator::encode(array(true, false, true))
		);
	}

	/**
	* @test
	* @depends encodeArray_can_encode_arrays_to_objects
	*/
	public function encodeArray_can_preserve_a_key_of_an_array()
	{
		$arr = array(
			'a' => 1,
			'b' => 2
		);

		$struct = array(
			'preserveKeys' => array(
				array('a')
			)
		);

		$this->assertSame(
			'{"a":1,b:2}',
			$this->encodeArray($arr, $struct)
		);
	}

	/**
	* @test
	* @depends encodeArray_can_preserve_a_key_of_an_array
	*/
	public function encodeArray_can_preserve_a_key_of_a_nested_array()
	{
		$arr = array(
			'a' => array('z' => 1, 'b' => 2),
			'b' => 2
		);

		$struct = array(
			'preserveKeys' => array(
				array('a', 'z')
			)
		);

		$this->assertSame(
			'{a:{"z":1,b:2},b:2}',
			$this->encodeArray($arr, $struct)
		);
	}

	/**
	* @ŧest
	* @depends encodeArray_can_preserve_a_key_of_a_nested_array
	*/
	public function encodeArray_preserves_keys_at_the_correct_depth()
	{
		$arr = array(
			'a' => array('a' => 1, 'b' => 2),
			'b' => 2
		);

		$struct = array(
			'preserveKeys' => array(
				array('a', 'a')
			)
		);

		$this->assertSame(
			'{a:{"a":1,b:2},b:2}',
			$this->encodeArray($arr, $struct)
		);
	}

	/**
	* @test
	* @depends encodeArray_can_preserve_a_key_of_an_array
	*/
	public function encodeArray_can_use_TRUE_as_a_wildcard()
	{
		$arr = array(
			'a' => array('a' => 1, 'b' => 2),
			'b' => array('a' => 1, 'b' => 2)
		);

		$struct = array(
			'preserveKeys' => array(
				array('a', true)
			)
		);

		$this->assertSame(
			'{a:{"a":1,"b":2},b:{a:1,b:2}}',
			$this->encodeArray($arr, $struct)
		);
	}

	/**
	* @test
	*/
	public function encodeArray_preserves_reserved_words()
	{
		$arr = array(
			'a'    => 1,
			'with' => 2
		);

		$this->assertSame(
			'{a:1,"with":2}',
			$this->encodeArray($arr)
		);
	}

	/**
	* @test
	* @dataProvider deadCodeProvider
	*/
	public function Useless_code_is_removed_from_the_source($funcNames, $keepConfig, $removeConfig = array())
	{
		$regexps = array();

		foreach ((array) $funcNames as $funcName)
		{
			$regexps[$funcName] = '#function ' . $funcName . '\\([^\\)]*\\)\\s*\\{\\s*\\}#';
		}

		$this->cb->addTag('B');
		$this->cb->addTag('A', $removeConfig);

		// First we test that the code is removed by default
		foreach ($regexps as $funcName => $regexp)
		{
			$this->assertRegExp(
				$regexp,
				$this->cb->getJSParser(array('compilation' => 'none')),
				$funcName . ' did not get removed'
			);
		}

		$this->cb->removeTag('A');
		$this->cb->addTag('A', $keepConfig);

		// Then we make sure it's not applicable
		foreach ($regexps as $funcName => $regexp)
		{
			$this->assertNotRegExp(
				$regexp,
				$this->cb->getJSParser(array('compilation' => 'none')),
				$funcName . ' incorrectly got removed'
			);
		}
	}

	public function deadCodeProvider()
	{
		return array(
			// rules
			array('closeParent',      array('rules' => array('closeParent' => array('B')))),
			array('closeAncestor',   array('rules' => array('closeAncestor' => array('B')))),
			array('requireParent',    array('rules' => array('requireParent' => array('B')))),
			array('requireAncestor', array('rules' => array('requireAncestor' => array('B')))),

			// attributes
			array(
				'currentTagRequiresMissingAttribute',
				array('attrs' => array('foo' => array('type' => 'int', 'isRequired' => true))),
				array('attrs' => array('foo' => array('type' => 'int', 'isRequired' => false)))
			),
			array(
				array('filterAttributes', 'filter'),
				array('attrs' => array('foo' => array('type' => 'int')))
			),
			array(
				'splitCompoundAttributes',
				array('attrs' => array('foo' => array('type' => 'compound')))
			),
			array(
				'addDefaultAttributeValuesToCurrentTag',
				array('attrs' => array('foo' => array('type' => 'int', 'defaultValue' => 42)))
			),

			// callbacks
			array(
				array('applyCallback', 'applyTagPreFilterCallbacks'),
				array('preFilter' => array(array('callback' => 'array_unique')))
			),
			array(
				array('applyCallback', 'applyTagPostFilterCallbacks'),
				array('postFilter' => array(array('callback' => 'array_unique')))
			),
			array(
				array('applyCallback', 'applyAttributePreFilterCallbacks'),
				array(
					'attrs' => array(
						'foo' => array(
							'type' => 'int',
							'preFilter' => array(array('callback' => 'trim'))
						)
					)
				)
			),
			array(
				array('applyCallback', 'applyAttributePostFilterCallbacks'),
				array(
					'attrs' => array(
						'foo' => array(
							'type' => 'int',
							'postFilter' => array(array('callback' => 'trim'))
						)
					)
				)
			),

			// whitespace trimming
			array(
				'addTrimmingInfoToTag',
				array('trimBefore' => true)
			),
			array(
				'addTrimmingInfoToTag',
				array('trimAfter' => true)
			),
			array(
				'addTrimmingInfoToTag',
				array('ltrimContent' => true)
			),
			array(
				'addTrimmingInfoToTag',
				array('rtrimContent' => true)
			),
		);
	}

	/**
	* @test
	*/
	public function generateFiltersConfig_return_allowedSchemes_regexp_as_an_object()
	{
		$this->call($this->jspg, 'init');

		$this->assertContains(
			'allowedSchemes:/^https?$/i',
			$this->call($this->jspg, 'generateFiltersConfig')
		);
	}

	/**
	* @test
	*/
	public function generateFiltersConfig_return_disallowedHosts_regexp_as_an_object()
	{
		$this->cb->disallowHost('example.com');
		$this->call($this->jspg, 'init');

		$this->assertContains(
			'disallowedHosts:/',
			$this->call($this->jspg, 'generateFiltersConfig')
		);
	}

	/**
	* @test
	* @depends generateFiltersConfig_return_disallowedHosts_regexp_as_an_object
	*/
	public function generateFiltersConfig_converts_unsupported_lookbehind_assertions_from_disallowedHosts_regexp()
	{
		$this->cb->disallowHost('example.com');
		$this->call($this->jspg, 'init');

		$this->assertContains(
			'/(?:^|\\.)example\\.com$/i',
			$this->call($this->jspg, 'generateFiltersConfig')
		);
	}

	/**
	* @test
	* @depends encodeArray_can_encode_arrays_to_objects
	*/
	public function encodeConfig_removes_parserClassName_from_config()
	{
		$this->assertSame(
			'{foo:1}',
			$this->encodeConfig(
				array(
					'parserClassName' => 'foo',
					'foo' => 1
				),
				array()
			)
		);
	}

	/**
	* @test
	* @depends encodeArray_can_encode_arrays_to_objects
	*/
	public function encodeConfig_removes_parserFilepath_from_config()
	{
		$this->assertSame(
			'{foo:1}',
			$this->encodeConfig(
				array(
					'parserFilepath' => 'foo',
					'foo' => 1
				),
				array()
			)
		);
	}

	/**
	* @test
	* @depends encodeArray_can_encode_arrays_to_objects
	*/
	public function encodeConfig_convert_scalar_regexp_to_a_RegExp_object_with_g_flag()
	{
		$this->assertSame(
			'{regexp:/foo/g}',
			$this->encodeConfig(
				array(
					'regexp' => '#foo#'
				),
				array()
			)
		);
	}

	/**
	* @test
	* @depends encodeArray_can_encode_arrays_to_objects
	*/
	public function encodeConfig_convert_array_regexp_to_an_object_with_RegExp_objects_with_g_flag_as_properties()
	{
		$this->assertSame(
			'{regexp:{bar:/bar/g,baz:/baz/g}}',
			$this->encodeConfig(
				array(
					'regexp' => array(
						'bar' => '#bar#',
						'baz' => '#baz#'
					)
				),
				array()
			)
		);
	}

	/**
	* @test
	*/
	public function Injects_plugins_parsers_into_source()
	{
		$this->cb->loadPlugin('Autolink');

		$jsParser = $this->jspg->get();

		$this->assertContains(
			'pluginParsers = {"Autolink":function',
			$jsParser
		);
	}

	/**
	* @test
	*/
	public function Injects_plugins_configs_into_source()
	{
		$this->cb->loadPlugin('Autolink');

		$jsParser = $this->jspg->get();

		$this->assertContains(
			'pluginsConfig = {"Autolink":{',
			$jsParser
		);
	}

	/**
	* @testdox replaceConstant() throws an exception if no replacement occurs
	* @expectedException RuntimeException
	* @expectedExceptionMessage Tried to replace constant UNKNOWN, 0 occurences found
	*/
	public function testReplaceConstantFailZeroMatch()
	{
		$this->call($this->jspg, 'init');
		$this->call($this->jspg, 'replaceConstant', array('UNKNOWN', 2));
	}

	/**
	* @testdox convertRegexp() can convert plain regexps
	*/
	public function testConvertRegexp1()
	{
		$this->assertEquals(
			'/foo/',
			JSParserGenerator::convertRegexp('#foo#')
		);
	}

	/**
	* @testdox convertRegexp() escapes forward slashes
	*/
	public function testConvertRegexpEscape()
	{
		$this->assertEquals(
			'/fo\\/o/',
			JSParserGenerator::convertRegexp('#fo/o#')
		);
	}

	/**
	* @testdox convertRegexp() does not double-escape forward slashes that are already escaped
	*/
	public function testConvertRegexpNoDoubleEscape()
	{
		$this->assertEquals(
			'/fo\\/o/',
			JSParserGenerator::convertRegexp('#fo\\/o#')
		);
	}

	/**
	* @testdox convertRegexp() does not "eat" backslashes while escaping forward slashes
	*/
	public function testConvertRegexpDoesNotEatEscapedBackslashes()
	{
		$this->assertEquals(
			'/fo\\\\\\/o/',
			JSParserGenerator::convertRegexp('#fo\\\\/o#')
		);
	}

	/**
	* @testdox convertRegexp() can convert regexps with the "i" modifier
	*/
	public function testConvertRegexp2()
	{
		$this->assertEquals(
			'/foo/i',
			JSParserGenerator::convertRegexp('#foo#i')
		);
	}

	/**
	* @testdox convertRegexp() can convert regexps with capturing subpatterns
	*/
	public function testConvertRegexp3()
	{
		$this->assertEquals(
			'/f(o)o/',
			JSParserGenerator::convertRegexp('#f(o)o#')
		);
	}

	/**
	* @testdox convertRegexp() can convert regexps with non-capturing subpatterns
	*/
	public function testConvertRegexp4()
	{
		$this->assertEquals(
			'/f(?:o)o/',
			JSParserGenerator::convertRegexp('#f(?:o)o#')
		);
	}

	/**
	* @testdox convertRegexp() can convert regexps with non-capturing subpatterns with a quantifier
	*/
	public function testConvertRegexp5()
	{
		$this->assertEquals(
			'/f(?:oo)+/',
			JSParserGenerator::convertRegexp('#f(?:oo)+#')
		);
	}

	/**
	* @testdox convertRegexp() throws a RuntimeException on options (?i)
	* @expectedException RuntimeException
	* @expectedExceptionMessage Regexp options are not supported
	*/
	public function testConvertRegexpException1()
	{
		JSParserGenerator::convertRegexp('#(?i)x#');
	}

	/**
	* @testdox convertRegexp() throws a RuntimeException on subpattern options (?i:)
	* @expectedException RuntimeException
	* @expectedExceptionMessage Subpattern options are not supported
	*/
	public function testConvertRegexpException2()
	{
		JSParserGenerator::convertRegexp('#(?i:x)#');
	}

	/**
	* @testdox convertRegexp() can convert regexps with character classes with a quantifier
	*/
	public function testConvertRegexp6()
	{
		$this->assertEquals(
			'/[a-z]+/',
			JSParserGenerator::convertRegexp('#[a-z]+#')
		);
	}

	/**
	* @testdox convertRegexp() replaces \pL with the full character range in character classes
	*/
	public function testConvertRegexp7()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[0-9' . $unicodeRange . '\\]/$#D',
			JSParserGenerator::convertRegexp('#[0-9\\pL]#')
		);
	}

	/**
	* @testdox convertRegexp() replaces \p{L} with the full character range in character classes
	*/
	public function testConvertRegexp7b()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[0-9' . $unicodeRange . '\\]/$#D',
			JSParserGenerator::convertRegexp('#[0-9\\p{L}]#')
		);
	}

	/**
	* @testdox convertRegexp() replaces \pL outside of character classes with a character class containing the full character range
	*/
	public function testConvertRegexp8()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[' . $unicodeRange . '\\]00\\[' . $unicodeRange . '\\]/$#D',
			JSParserGenerator::convertRegexp('#\\pL00\\pL#')
		);
	}

	/**
	* @testdox convertRegexp() replaces \p{L} outside of character classes with a character class containing the full character range
	*/
	public function testConvertRegexp8b()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[' . $unicodeRange . '\\]00\\[' . $unicodeRange . '\\]/$#D',
			JSParserGenerator::convertRegexp('#\\p{L}00\\p{L}#')
		);
	}

	/**
	* @testdox convertRegexp() can convert regexps with lookahead assertions
	*/
	public function testConvertRegexpLookahead()
	{
		$this->assertEquals(
			'/(?=foo)|(?=bar)/',
			JSParserGenerator::convertRegexp('#(?=foo)|(?=bar)#')
		);
	}

	/**
	* @testdox convertRegexp() can convert regexps with negative lookahead assertions
	*/
	public function testConvertRegexpNegativeLookahead()
	{
		$this->assertEquals(
			'/(?!foo)|(?!bar)/',
			JSParserGenerator::convertRegexp('#(?!foo)|(?!bar)#')
		);
	}

	/**
	* @testdox convertRegexp() throws a RuntimeException on lookbehind assertions
	* @expectedException RuntimeException
	* @expectedExceptionMessage Lookbehind assertions are not supported
	*/
	public function testConvertRegexpExceptionOnLookbehind()
	{
		JSParserGenerator::convertRegexp('#(?<=foo)x#');
	}

	/**
	* @testdox convertRegexp() throws a RuntimeException on negative lookbehind assertions
	* @expectedException RuntimeException
	* @expectedExceptionMessage Negative lookbehind assertions are not supported
	*/
	public function testConvertRegexpExceptionOnNegativeLookbehind()
	{
		JSParserGenerator::convertRegexp('#(?<!foo)x#');
	}
}