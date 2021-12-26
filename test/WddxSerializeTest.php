<?php
/** @noinspection PhpUnusedPrivateFieldInspection */
/** @noinspection PhpMissingFieldTypeInspection */
/** @noinspection HtmlUnknownAttribute */
/** @noinspection HtmlUnknownAttribute */
/** @noinspection RequiredAttributes */

declare(strict_types=1);

namespace Mumasoft\WddxShim;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Test case for WDDX serialization
 */
class WddxSerializeTest extends TestCase
{
	private WddxSerializer $wddx;

	protected function setUp(): void
	{
		$this->wddx = new WddxSerializer();
	}

	/**
	 * Tests behaviour when serializing null
	 * @return void
	 */
	public function testSerializeNull(): void
	{
		$this->assertEquals("<wddxPacket version=\"1.0\"><header/><data><null/></data></wddxPacket>",
			$this->wddx->serializeValue(null));
	}

	/**
	 * Tests behaviour when serializing null and test comment addition
	 * @return void
	 */
	public function testSerializeNullWithComment(): void
	{
		$comment = 'test';
		$this->assertEquals("<wddxPacket version=\"1.0\"><header><comment>$comment</comment></header><data><null/></data></wddxPacket>",
			$this->wddx->serializeValue(null, $comment));
	}

	/**
	 * Tests serialization of a single string
	 * @return void
	 */
	public function testSerializeString(): void
	{
		$this->assertEquals("<wddxPacket version=\"1.0\"><header/><data><string>test</string></data></wddxPacket>",
			$this->wddx->serializeValue('test'));
	}

	/**
	 * Tests serialization of an int value.
	 * @return void
	 */
	public function testSerializeInt()
	{
		$int = 1;
		$this->assertEquals("<wddxPacket version=\"1.0\"><header/><data><number>$int</number></data></wddxPacket>",
			$this->wddx->serializeValue($int));
	}

	public function testSerializeFloat()
	{
		$float = 10.11;
		$this->assertEquals("<wddxPacket version=\"1.0\"><header/><data><number>$float</number></data></wddxPacket>",
			$this->wddx->serializeValue($float));
	}

	public function testSerializeFalse()
	{
		$this->assertEquals("<wddxPacket version=\"1.0\"><header/><data><boolean value=\"false\"/></data></wddxPacket>",
			$this->wddx->serializeValue(false));
	}

	public function testSerializeTrue()
	{
		$this->assertEquals("<wddxPacket version=\"1.0\"><header/><data><boolean value=\"true\"/></data></wddxPacket>",
			$this->wddx->serializeValue(true));
	}

	public function testSerializeArrayList()
	{
		$array = ['foo', 'bar'];
		$this->assertEquals("<wddxPacket version=\"1.0\"><header/><data><array length=\"2\"><string>foo</string><string>bar</string></array></data></wddxPacket>",
			$this->wddx->serializeValue($array));
	}

	public function testSerializeAssociativeArray()
	{
		$array = ['foo' => 'bar', 'baz' => 'quux'];
		$this->assertEquals("<wddxPacket version=\"1.0\"><header/><data><struct><var name=\"foo\"><string>bar</string></var><var name=\"baz\"><string>quux</string></var></struct></data></wddxPacket>",
			$this->wddx->serializeValue($array));
	}

	public function testSerializeAssociativeArrayRecursive()
	{
		$array = ['foo' => 'bar', 'baz' => ['a' => 'b']];
		$this->assertEquals('<wddxPacket version="1.0"><header/><data><struct><var name="foo"><string>bar</string></var><var name="baz"><struct><var name="a"><string>b</string></var></struct></var></struct></data></wddxPacket>',
			$this->wddx->serializeValue($array));
	}

	public function testSerializeStdClass()
	{
		$s = new stdClass();
		$s->foo = 'bar';
		$s->baz = 'quux';
		$this->assertEquals('<wddxPacket version="1.0"><header/><data><struct><var name="php_class_name"><string>stdClass</string></var><var name="foo"><string>bar</string></var><var name="baz"><string>quux</string></var></struct></data></wddxPacket>',
			$this->wddx->serializeValue($s));
	}

	public function testSerializeObject()
	{
		$dto = new SerializeDto();
		$this->assertEquals('<wddxPacket version="1.0"><header/><data><struct><var name="php_class_name"><string>Mumasoft\WddxShim\SerializeDto</string></var><var name="foo"><string>bar</string></var><var name="baz"><string>quux</string></var></struct></data></wddxPacket>',
			$this->wddx->serializeValue($dto));
	}

	public function testSerializeObjectInifiteRecursion()
	{
		// An invalid exception should be thrown
		$this->expectException(InvalidArgumentException::class);

		$dto = new stdClass();
		$dto->self = $dto;
		$this->wddx->serializeValue($dto);
	}
}
