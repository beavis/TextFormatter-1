<?php

namespace s9e\TextFormatter\Tests\Configurator;

use stdClass;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\JavaScript;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\ClosureCompilerService;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension json
* @covers s9e\TextFormatter\Configurator\JavaScript
*/
class JavaScriptTest extends Test
{
	public function setUp()
	{
		$this->configurator->enableJavaScript();
	}

	/**
	* @testdox getMinifier() returns an instance of Noop by default
	*/
	public function testGetMinifier()
	{
		$javascript = new JavaScript(new Configurator);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\Noop',
			$javascript->getMinifier()
		);
	}

	/**
	* @testdox setMinifier() accepts the name of a minifier type and returns an instance
	*/
	public function testSetMinifierName()
	{
		$javascript = new JavaScript(new Configurator);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\ClosureCompilerService',
			$javascript->setMinifier('ClosureCompilerService')
		);
	}

	/**
	* @testdox setMinifier() accepts the name of a minifier type plus any number of arguments passed to the minifier's constructor
	*/
	public function testSetMinifierArguments()
	{
		$javascript = new JavaScript(new Configurator);
		$command    = 'npx google-closure-compiler';

		$this->assertSame(
			$command,
			$javascript->setMinifier('ClosureCompilerApplication', $command)->command
		);
	}

	/**
	* @testdox setMinifier() accepts an object that implements Minifier
	*/
	public function testSetMinifierInstance()
	{
		$javascript = new JavaScript(new Configurator);
		$minifier   = new ClosureCompilerService;

		$javascript->setMinifier($minifier);

		$this->assertSame($minifier, $javascript->getMinifier());
	}

	/**
	* @testdox setMinifier() returns the new instance
	*/
	public function testSetMinifierReturn()
	{
		$javascript  = new JavaScript(new Configurator);
		$oldMinifier = $javascript->getMinifier();

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\Noop',
			$javascript->setMinifier('Noop')
		);

		$this->assertNotSame($oldMinifier, $javascript->getMinifier());
	}

	/**
	* @testdox getParser() calls the minifier and returns its result
	*/
	public function testMinifierReturn()
	{
		$mock = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\JavaScript\\Minifier')
		             ->getMock();
		$mock->expects($this->once())
		     ->method('get')
		     ->will($this->returnValue('/**/'));

		$this->configurator->enableJavaScript();
		$this->configurator->javascript->setMinifier($mock);

		$this->assertContains('/**/', $this->configurator->javascript->getParser());
	}

	/**
	* @testdox A plugin's quickMatch value is preserved if it's valid UTF-8
	*/
	public function testQuickMatchUTF8()
	{
		$this->configurator->plugins->load('Escaper', ['quickMatch' => 'foo']);

		$this->assertContains(
			'quickMatch:"foo"',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox A plugin's quickMatch value is discarded if it contains no valid UTF-8
	*/
	public function testQuickMatchUTF8Bad()
	{
		$this->configurator->plugins->load('Escaper', ['quickMatch' => "\xC0"]);

		$this->assertNotContains(
			'quickMatch:',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox If a plugin's quickMatch value contains bad UTF-8, only the first consecutive valid characters are kept
	*/
	public function testQuickMatchUTF8Partial()
	{
		$this->configurator->plugins->load('Escaper', ['quickMatch' => "\xC0xÿz"]);

		$this->assertContains(
			'quickMatch:"x\\u00ffz"',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox A plugin with no JS parser does not appear in the source
	*/
	public function testPluginNoParser()
	{
		$this->configurator->plugins['Foobar'] = new NoJSPluginConfigurator($this->configurator);

		$this->assertNotContains(
			'Foobar',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox getParser() throws an exception if it encounters a value that cannot be encoded into JavaScript
	* @expectedException RuntimeException
	* @expectedExceptionMessage Cannot encode instance of stdClass
	*/
	public function testNonScalarConfigException()
	{
		$this->configurator->registeredVars['foo'] = new NonScalarConfigThing;
		$this->configurator->javascript->getParser();
	}

	/**
	* @testdox Built-in attribute filters are converted
	*/
	public function testAttributeFilterBuiltIn()
	{
		$this->configurator->rootRules->allowChild('FOO');
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain->append(
			$this->configurator->attributeFilters->get('#int')
		);

		$js = $this->configurator->javascript->getParser();

		$this->assertContains(
			'function(attrValue,attrName){return NumericFilter.filterInt(attrValue);}',
			$js
		);
	}

	/**
	* @testdox An attribute filter that uses a built-in filter as callback is converted
	*/
	public function testAttributeFilterBuiltInCallback()
	{
		$this->configurator->rootRules->allowChild('FOO');
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain->append(
			's9e\\TextFormatter\\Parser\\AttributeFilters\\NumericFilter::filterInt'
		);

		$js = $this->configurator->javascript->getParser();

		$this->assertContains(
			'function(tag,tagConfig){return filterAttributes(tag,tagConfig,registeredVars,logger);}',
			$js
		);
	}

	/**
	* @testdox An attribute filter with no JS representation unconditionally returns false
	*/
	public function testAttributeFilterMissing()
	{
		$this->configurator->rootRules->allowChild('FOO');
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain->append(
			function() {}
		);

		$js = $this->configurator->javascript->getParser();

		$this->assertContains('filterChain:[returnFalse]', $js);
	}

	/**
	* @testdox The name of a registered vars is expressed in quotes
	*/
	public function testRegisteredVarBracket()
	{
		$this->configurator->registeredVars = ['foo' => 'bar'];

		$this->assertContains(
			'registeredVars={"foo":"bar"}',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox "cacheDir" is removed from registered vars
	*/
	public function testRegisteredVarCacheDir()
	{
		$this->configurator->registeredVars = ['cacheDir' => '', 'foo' => 'bar'];

		$src = $this->configurator->javascript->getParser();

		$this->assertContains('registeredVars={"foo":"bar"}', $src);
		$this->assertNotContains('cacheDir', $src);
	}

	/**
	* @testdox "className" is removed from the plugins' config
	*/
	public function testOmitClassName()
	{
		$this->configurator->Autolink;

		$src = $this->configurator->javascript->getParser();

		$this->assertNotContains('className', $src);
	}

	/**
	* @testdox Callbacks use the bracket syntax to access registered vars
	*/
	public function testCallbackRegisteredVarBracket()
	{
		$this->configurator->registeredVars = ['foo' => 'bar'];

		$this->configurator->rootRules->allowChild('FOO');
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain
			->append('strtolower')
			->resetParameters()
			->addParameterByName('foo');

		$this->assertContains(
			'registeredVars["foo"]',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox The normal Logger is present by default
	*/
	public function testLoggerDefault()
	{
		$js = $this->configurator->javascript->getParser();

		$this->assertContains(
			file_get_contents(__DIR__ . '/../../src/Parser/Logger.js'),
			$js
		);

		$this->assertNotContains(
			file_get_contents(__DIR__ . '/../../src/Parser/NullLogger.js'),
			$js
		);
	}

	/**
	* @testdox The null Logger is use if getLogger() is not exported
	*/
	public function testNullLogger()
	{
		$this->configurator->javascript->exports = ['preview'];
		$js = $this->configurator->javascript->getParser();

		$this->assertNotContains(
			file_get_contents(__DIR__ . '/../../src/Parser/Logger.js'),
			$js
		);

		$this->assertContains(
			file_get_contents(__DIR__ . '/../../src/Parser/NullLogger.js'),
			$js
		);
	}

	/**
	* @testdox Callbacks correctly encode values
	*/
	public function testCallbackValue()
	{
		$this->configurator->rootRules->allowChild('FOO');
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain
			->append('strtolower')
			->resetParameters()
			->addParameterByValue('foo')
			->addParameterByValue(42);

		$this->assertContains(
			'("foo",42)',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox Tag config is wholly deduplicated
	*/
	public function testOptimizeWholeTag()
	{
		$this->configurator->rootRules->allowChild('X');
		$this->configurator->rootRules->allowChild('Y');
		$this->configurator->tags->add('X');
		$this->configurator->tags->add('Y');
		$this->configurator->javascript->exports = ['preview'];
		$js = $this->configurator->javascript->getParser();

		$this->assertNotContains('"X":{', $js);
		$this->assertNotContains('"Y":{', $js);
		$this->assertRegexp('(tagsConfig=\\{"X":(\\w+),"Y":\\1)', $js);
	}

	/**
	* @testdox The public API is created if anything is exported
	*/
	public function testExport()
	{
		$this->configurator->javascript->exports = ['preview'];
		$this->assertContains("window['s9e']", $this->configurator->javascript->getParser());
	}

	/**
	* @testdox The public API is not created if nothing is exported
	*/
	public function testNoExport()
	{
		$this->configurator->javascript->exports = [];
		$this->assertNotContains("window['s9e']['TextFormatter']", $this->configurator->javascript->getParser());
	}

	/**
	* @testdox Exports' order is consistent
	*/
	public function testExportOrder()
	{
		$this->configurator->javascript->exports = ['parse', 'preview'];
		$js1 = $this->configurator->javascript->getParser();

		$this->configurator->javascript->exports = ['preview', 'parse'];
		$js2 = $this->configurator->javascript->getParser();

		$this->assertSame($js1, $js2);
	}

	/**
	* @testdox $exportMethods is an alias for $exports
	*/
	public function testExportMethods()
	{
		$this->assertSame(
			$this->configurator->javascript->exports,
			$this->configurator->javascript->exportMethods
		);
		$this->configurator->javascript->exportMethods = ['registerVars', 'preview'];
		$this->assertSame(
			$this->configurator->javascript->exports,
			$this->configurator->javascript->exportMethods
		);
	}

	/**
	* @testdox Preserves live preview attributes
	*/
	public function testLivePreviewAttributes()
	{
		$this->configurator->tags->add('X')->template = '<hr data-s9e-livepreview-ignore-attrs="style"/>';

		$this->assertContains('data-s9e-livepreview-ignore-attrs', $this->configurator->javascript->getParser());
	}
}

class NonScalarConfigThing implements ConfigProvider
{
	public function asConfig()
	{
		return ['foo' => new stdClass];
	}
}

class NoJSPluginConfigurator extends ConfiguratorBase
{
	public function asConfig()
	{
		return ['regexp' => '//'];
	}
}