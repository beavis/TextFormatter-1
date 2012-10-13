<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Items;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Collections\AttributePreprocessorCollection;
use s9e\TextFormatter\ConfigBuilder\Collections\Ruleset;
use s9e\TextFormatter\ConfigBuilder\Collections\Templateset;
use s9e\TextFormatter\ConfigBuilder\Items\Tag;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Items\Tag
*/
class TagTest extends Test
{
	/**
	* @testdox An array of options can be passed to the constructor
	*/
	public function testConstructorOptions()
	{
		$tag = new Tag(array('nestingLimit' => 123));
		$this->assertSame(123, $tag->nestingLimit);
	}

	/**
	* @testdox $tag->attributePreprocessors can be assigned a 2D array of regexps
	*/
	public function testAttributePreprocessorsArray()
	{
		$attributePreprocessors = array(
			'foo' => array('/a/', '/b/'),
			'bar' => array('/c/')
		);

		$tag = new Tag;
		$tag->attributePreprocessors = $attributePreprocessors;

		$this->assertEquals(
			$attributePreprocessors,
			$tag->attributePreprocessors->getConfig()
		);
	}

	/**
	* @testdox $tag->attributePreprocessors can be assigned an instance of AttributePreprocessorCollection to copy its content
	*/
	public function testAttributePreprocessorsInstanceOfAttributePreprocessorCollection()
	{
		$attributePreprocessorCollection = new AttributePreprocessorCollection;
		$attributePreprocessorCollection->add('foo', '/bar/');

		$tag = new Tag;
		$tag->attributePreprocessors = $attributePreprocessorCollection;

		$this->assertEquals(
			$attributePreprocessorCollection,
			$tag->attributePreprocessors
		);

		$this->assertNotSame(
			$attributePreprocessorCollection,
			$tag->attributePreprocessors,
			'$tag->attributePreprocessor should not have been replaced with $attributePreprocessorCollection'
		);
	}

	/**
	* @testdox Setting $tag->attributePreprocessors clears previous attributePreprocessors
	* @depends testAttributePreprocessorsArray
	*/
	public function testAttributePreprocessorsArrayClears()
	{
		$attributePreprocessors = array(
			'foo' => array('/a/', '/b/'),
			'bar' => array('/c/')
		);

		$tag = new Tag;
		$tag->attributePreprocessors->add('baz', '/d/');
		$tag->attributePreprocessors = $attributePreprocessors;

		$this->assertEquals(
			$attributePreprocessors,
			$tag->attributePreprocessors->getConfig()
		);
	}

	/**
	* @testdox $tag->nestingLimit accepts '10' and casts it as an integer
	*/
	public function testNestingLimitString()
	{
		$tag = new Tag;
		$tag->nestingLimit = '10';
		$this->assertSame(10, $tag->nestingLimit);
	}

	/**
	* @testdox $tag->nestingLimit rejects non-numbers
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage nestingLimit must be a number greater than 0
	*/
	public function testNestingLimitNonNumber()
	{
		$tag = new Tag;
		$tag->nestingLimit = 'invalid';
	}

	/**
	* @testdox $tag->nestingLimit rejects numbers less than 1
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage nestingLimit must be a number greater than 0
	*/
	public function testNestingLimitLessThanOne()
	{
		$tag = new Tag;
		$tag->nestingLimit = -1;
	}

	/**
	* @testdox $tag->rules can be assigned a 2D array of rules
	*/
	public function testRulesArray()
	{
		$rules = array(
			'allowChild' => array('B'),
			'denyChild'  => array('I')
		);

		$tag = new Tag;
		$tag->rules = $rules;

		$this->assertEquals($rules, iterator_to_array($tag->rules));
	}

	/**
	* @testdox $tag->rules can be assigned an instance of Ruleset to copy its content
	*/
	public function testRulesInstanceOfRuleset()
	{
		$ruleset = new Ruleset;
		$ruleset->allowChild('B');

		$tag = new Tag;
		$tag->rules = $ruleset;

		$this->assertEquals($ruleset, $tag->rules);
		$this->assertNotSame($ruleset, $tag->rules, '$tag->rules should not have been replaced with $ruleset');
	}

	/**
	* @testdox Setting $tag->rules clears previous rules
	* @depends testRulesArray
	*/
	public function testRulesArrayClears()
	{
		$rules = array(
			'allowChild' => array('B'),
			'denyChild'  => array('I')
		);

		$tag = new Tag;
		$tag->rules->allowChild('U');
		$tag->rules = $rules;

		$this->assertEquals($rules, iterator_to_array($tag->rules));
	}

	/**
	* @testdox $tag->tagLimit accepts '10' and casts it as an integer
	*/
	public function testTagLimitString()
	{
		$tag = new Tag;
		$tag->tagLimit = '10';
		$this->assertSame(10, $tag->tagLimit);
	}

	/**
	* @testdox $tag->tagLimit rejects non-numbers
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage tagLimit must be a number greater than 0
	*/
	public function testTagLimitNonNumber()
	{
		$tag = new Tag;
		$tag->tagLimit = 'invalid';
	}

	/**
	* @testdox $tag->tagLimit rejects numbers less than 1
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage tagLimit must be a number greater than 0
	*/
	public function testTagLimitLessThanOne()
	{
		$tag = new Tag;
		$tag->tagLimit = -1;
	}

	/**
	* @testdox $tag->templates can be assigned an array of templates
	*/
	public function testTemplatesArray()
	{
		$templates = array(
			''     => 'first',
			'@foo' => 'second'
		);

		$tag = new Tag;
		$tag->templates = $templates;

		$this->assertEquals($templates, iterator_to_array($tag->templates));
	}

	/**
	* @testdox $tag->templates can be assigned an instance of Templateset to copy its content
	*/
	public function testTemplatesInstanceOfTemplateset()
	{
		$tag = new Tag;

		$templateset = new Templateset($tag);
		$templateset->setUnsafe('', 'foo');

		$tag->templates = $templateset;

		$this->assertEquals($templateset, $tag->templates);
		$this->assertNotSame($templateset, $tag->templates, '$tag->templates should not have been replaced with $templateset');
	}

	/**
	* @testdox Setting $tag->templates clears previous templates
	* @depends testTemplatesArray
	*/
	public function testTemplatesArrayClears()
	{
		$templates = array(
			''     => 'first',
			'@foo' => 'second'
		);

		$tag = new Tag;
		$tag->templates = array('' => 'deleteme');
		$tag->templates = $templates;

		$this->assertEquals($templates, iterator_to_array($tag->templates));
	}

	/**
	* @testdox setTemplates() throws an InvalidArgumentException if its argument is not an array or an instance of Templateset
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage setTemplates() expects an array or an instance of Templateset
	*/
	public function testSetTemplatesInvalid()
	{
		$tag = new Tag;
		$tag->templates = false;
	}

	/**
	* @testdox $tag->defaultTemplate = 'foo' maps to $tag->templates->set('', 'foo')
	*/
	public function testSetDefaultTemplate()
	{
		$tag = new Tag;
		$tag->defaultTemplate = 'foo';

		$this->assertSame('foo', $tag->templates->get(''));
	}
}