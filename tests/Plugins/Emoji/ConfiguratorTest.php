<?php

namespace s9e\TextFormatter\Tests\Plugins\Emoji;

use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Plugins\Emoji\Configurator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Emoji\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates an "EMOJI" tag
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('Emoji');
		$this->assertTrue($this->configurator->tags->exists('EMOJI'));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tag = $this->configurator->tags->add('EMOJI');
		$this->configurator->plugins->load('Emoji');

		$this->assertSame($tag, $this->configurator->tags->get('EMOJI'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Emoji', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('Emoji', ['attrName' => 'bar']);
		$this->assertTrue($this->configurator->tags['EMOJI']->attributes->exists('bar'));
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$plugin = $this->configurator->plugins->load('Emoji', ['tagName' => 'FOO']);

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('FOO', $config['tagName']);
	}

	/**
	* @testdox The config array contains the name of the attribute
	*/
	public function testConfigAttrName()
	{
		$plugin = $this->configurator->plugins->load('Emoji', ['attrName' => 'bar']);

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('attrName', $config);
		$this->assertSame('bar', $config['attrName']);
	}

	/**
	* @testdox The config array contains no entry for custom aliases if there are none
	*/
	public function testConfigNoCustomAliases()
	{
		$plugin = $this->configurator->Emoji;
		$config = $plugin->asConfig();

		$this->assertArrayNotHasKey('customQuickMatch', $config);
		$this->assertArrayNotHasKey('customRegexp',     $config);
	}

	/**
	* @testdox A variable named Emoji.aliases is registered and contains aliases
	*/
	public function testConfigAliases()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':)', "\xF0\x9F\x98\x80");
		$plugin->finalize();

		$this->assertArrayHasKey('Emoji.aliases', $this->configurator->registeredVars);
		$this->assertArrayHasKey(':)', $this->configurator->registeredVars['Emoji.aliases']);
		$this->assertSame(
			"\xF0\x9F\x98\x80",
			$this->configurator->registeredVars['Emoji.aliases'][':)']
		);
	}

	/**
	* @testdox The config array contains a regexp for custom aliases if applicable
	*/
	public function testConfigAliasesRegexp()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':D', "\xF0\x9F\x98\x80");

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('customRegexp', $config);
		$this->assertEquals('/:D/', $config['customRegexp']);
	}

	/**
	* @testdox The config array contains a quickMatch for custom aliases if applicable
	*/
	public function testConfigAliasesQuickMatch()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':D', "\xF0\x9F\x98\x80");

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('customQuickMatch', $config);
		$this->assertEquals(':D', $config['customQuickMatch']);
	}

	/**
	* @testdox The config array does not contain a quickMatch for aliases if impossible
	*/
	public function testConfigAliasesNoQuickMatch()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':D', "\xF0\x9F\x98\x80");
		$plugin->addAlias(';)', "\xF0\x9F\x98\x80");

		$config = $plugin->asConfig();

		$this->assertArrayNotHasKey('customQuickMatch', $config);
	}

	/**
	* @testdox Uses EmojiOne by default
	*/
	public function testDefaultTemplateEmojiOne()
	{
		$this->configurator->Emoji;
		$this->assertContains('emojione', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox removeAlias() removes given alias
	*/
	public function testRemoveAlias()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':)', "\xF0\x9F\x98\x80");
		$plugin->addAlias(':D', "\xF0\x9F\x98\x80");
		$plugin->addAlias('XD', "\xF0\x9F\x98\x86");
		$plugin->removeAlias(':)');

		$this->assertArrayNotHasKey(':)', $plugin->getAliases());
	}

	/**
	* @testdox getJSHints() returns ['EMOJI_HAS_CUSTOM_REGEXP' => false] by default
	*/
	public function testGetJSHintsRegexpFalse()
	{
		$plugin = $this->configurator->Emoji;
		$this->assertArrayMatches(
			['EMOJI_HAS_CUSTOM_REGEXP' => false],
			$plugin->getJSHints()
		);
	}

	/**
	* @testdox getJSHints() returns ['EMOJI_HAS_CUSTOM_REGEXP' => true] if a custom regexp exists
	*/
	public function testGetJSHintsRegexpTrue()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':)', "\xF0\x9F\x98\x80");
		$this->assertArrayMatches(
			['EMOJI_HAS_CUSTOM_REGEXP' => true],
			$plugin->getJSHints()
		);
	}

	/**
	* @testdox getJSHints() returns ['EMOJI_HAS_CUSTOM_QUICKMATCH' => false] by default
	*/
	public function testGetJSHintsAliasQuickmatchFalse()
	{
		$plugin = $this->configurator->Emoji;
		$this->assertArrayMatches(
			['EMOJI_HAS_CUSTOM_QUICKMATCH' => false],
			$plugin->getJSHints()
		);
	}

	/**
	* @testdox getJSHints() returns ['EMOJI_HAS_CUSTOM_QUICKMATCH' => true] if an alias quick match exists
	*/
	public function testGetJSHintsAliasQuickmatchTrue()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':)', "\xF0\x9F\x98\x80");
		$plugin->addAlias(':D', "\xF0\x9F\x98\x80");
		$this->assertArrayMatches(
			['EMOJI_HAS_CUSTOM_QUICKMATCH' => true],
			$plugin->getJSHints()
		);
	}
		/**
	* @testdox $plugin->notAfter can be changed
	*/
	public function testNotAfter()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':)', "\xF0\x9F\x98\x80");
		$plugin->notAfter = '\\w';

		$config = ConfigHelper::filterConfig($plugin->asConfig(), 'PHP');

		$this->assertSame('/(?<!\\w):\)/', $config['customRegexp']);
	}

	/**
	* @testdox The plugin's modified JavaScript regexp is correctly converted
	*/
	public function testNotAfterPluginJavaScriptConversion()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':)', "\xF0\x9F\x98\x80");
		$plugin->addAlias('¬¬', "\xF0\x9F\x98\x80");
		$plugin->notAfter = '\\w';

		$config = ConfigHelper::filterConfig($plugin->asConfig(), 'JS');

		$this->assertEquals(new Code('/(?::\)|¬¬)/g'), $config['customRegexp']);
		$this->assertEquals(new Code('/\\w/'),        $config['notAfter']);
	}

	/**
	* @testdox The JavaScript regexp used for notAfter is correctly converted
	*/
	public function testNotAfterJavaScriptConversion()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':)', "\xF0\x9F\x98\x80");
		$plugin->notAfter = '(?>x)';

		$config = ConfigHelper::filterConfig($plugin->asConfig(), 'JS');

		$this->assertEquals(new Code('/:\)/g'),    $config['customRegexp']);
		$this->assertEquals(new Code('/(?:x)/'), $config['notAfter']);
	}

	/**
	* @testdox $plugin->notBefore can be changed
	*/
	public function testNotBefore()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':)', "\xF0\x9F\x98\x80");
		$plugin->notBefore = '\\w';

		$config = ConfigHelper::filterConfig($plugin->asConfig(), 'PHP');

		$this->assertSame('/:\)(?!\\w)/', $config['customRegexp']);
	}

	/**
	* @testdox $plugin->notAfter is removed from the JavaScript regexp and added separately to the config
	*/
	public function testNotAfterJavaScript()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':)', "\xF0\x9F\x98\x80");
		$plugin->notAfter = '\\w';

		$config = ConfigHelper::filterConfig($plugin->asConfig(), 'JS');

		$this->assertEquals(new Code('/:\)/g', 'g'), $config['customRegexp']);
		$this->assertEquals(new Code('/\\w/'),     $config['notAfter']);
	}

	/**
	* @testdox The regexp has the Unicode modifier if notAfter contains a Unicode property
	*/
	public function testNotAfterUnicode()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':)', "\xF0\x9F\x98\x80");
		$plugin->notAfter = '\\pL';

		$config = ConfigHelper::filterConfig($plugin->asConfig(), 'PHP');

		$this->assertSame('/(?<!\\pL):\)/u', $config['customRegexp']);
	}

	/**
	* @testdox The regexp has the Unicode modifier if notBefore contains a Unicode property
	*/
	public function testNotBeforeUnicode()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->addAlias(':)', "\xF0\x9F\x98\x80");
		$plugin->notBefore = '\\pL';

		$config = ConfigHelper::filterConfig($plugin->asConfig(), 'PHP');

		$this->assertSame('/:\)(?!\\pL)/u', $config['customRegexp']);
	}
	
	/**
	* @testdox getJSHints() returns ['EMOTICONS_NOT_AFTER' => 0] by default
	*/
	public function testGetJSHintsFalse()
	{
		$plugin = $this->configurator->Emoticons;
		$this->assertSame(
			['EMOTICONS_NOT_AFTER' => 0],
			$plugin->getJSHints()
		);
	}

	/**
	* @testdox getJSHints() returns ['EMOTICONS_NOT_AFTER' => 1] if notAfter is set
	*/
	public function testGetJSHintsTrue()
	{
		$plugin = $this->configurator->Emoticons;
		$plugin->notAfter = '\\w';
		$this->assertSame(
			['EMOTICONS_NOT_AFTER' => 1],
			$plugin->getJSHints()
		);
	}
}