<?php
/** @noinspection HtmlUnknownAttribute */
/** @noinspection HtmlUnknownAttribute */
/** @noinspection RequiredAttributes */

declare(strict_types=1);

namespace Mumasoft\WddxShim;

use PHPUnit\Framework\TestCase;
use stdClass;

class WddxDeserializeTest extends TestCase
{
	private WddxDeserializer $wddx;

	protected function setUp(): void
	{
		$this->wddx = new WddxDeserializer();
	}

	/**
	 * testDes behaviour when deserializing null
	 * @return void
	 */
	public function testDeserializeNull(): void
	{
		$this->assertNull(
			$this->wddx->deserialize("<wddxPacket version=\"1.0\"><header/><data><null/></data></wddxPacket>"));
	}

	/**
	 * testDes deserialization of a single string
	 * @return void
	 */
	public function testDeserializeString(): void
	{
		$this->assertEquals('test',
			$this->wddx->deserialize("<wddxPacket version=\"1.0\"><header/><data><string>test</string></data></wddxPacket>"));
	}

	/**
	 * testDes deserialization of an int value.
	 * @return void
	 */
	public function testDeserializeInt()
	{
		$this->assertEquals(1,
			$this->wddx->deserialize("<wddxPacket version=\"1.0\"><header/><data><number>1</number></data></wddxPacket>"));
	}

	public function testDeserializeFloat()
	{
		$this->assertEquals(10.11,
			$this->wddx->deserialize("<wddxPacket version=\"1.0\"><header/><data><number>10.11</number></data></wddxPacket>"));
	}

	public function testDeserializeFalse()
	{
		$this->assertFalse($this->wddx->deserialize("<wddxPacket version=\"1.0\"><header/><data><boolean value=\"false\"/></data></wddxPacket>"));
	}

	public function testDeserializeTrue()
	{
		$this->assertTrue($this->wddx->deserialize("<wddxPacket version=\"1.0\"><header/><data><boolean value=\"true\"/></data></wddxPacket>"));
	}

	public function testDeserializeArrayList()
	{
		$array = ['foo', 'bar'];
		$this->assertEquals($array,
			$this->wddx->deserialize("<wddxPacket version=\"1.0\"><header/><data><array length=\"2\"><string>foo</string><string>bar</string></array></data></wddxPacket>"));
	}

	public function testDeserializeAssociativeArray()
	{
		$array = ['foo' => 'bar', 'baz' => 'quux'];
		$this->assertEquals($array,
			$this->wddx->deserialize("<wddxPacket version=\"1.0\"><header/><data><struct><var name=\"foo\"><string>bar</string></var><var name=\"baz\"><string>quux</string></var></struct></data></wddxPacket>"));
	}

	public function testDeserializeAssociativeArray2()
	{
		$array = ['foo' => 'bar', 'baz' => 'quux', 'baz'];
		$this->assertEquals($array,
			$this->wddx->deserialize('<wddxPacket version="1.0"><header/><data><struct><var name="foo"><string>bar</string></var><var name="baz"><string>quux</string></var><var name="0"><string>baz</string></var></struct></data></wddxPacket>'));
	}

	public function testDeserializeAssociativeArrayRecursive()
	{
		$array = ['foo' => 'bar', 'baz' => ['a' => 'b']];
		$this->assertEquals($array,
			$this->wddx->deserialize('<wddxPacket version="1.0"><header/><data><struct><var name="foo"><string>bar</string></var><var name="baz"><struct><var name="a"><string>b</string></var></struct></var></struct></data></wddxPacket>'));
	}

	public function testDeserializeStdClass()
	{
		$s = new stdClass();
		$s->foo = 'bar';
		$s->baz = 'quux';
		$this->assertEquals($s,
			$this->wddx->deserialize('<wddxPacket version="1.0"><header/><data><struct><var name="php_class_name"><string>stdClass</string></var><var name="foo"><string>bar</string></var><var name="baz"><string>quux</string></var></struct></data></wddxPacket>'));
	}

	public function testDeserializeStdClassWithArray()
	{
		$s = new stdClass();
		$s->foo = 'bar';
		$s->baz = 'quux';
		$s->a = ['a', 'b'];
		$this->assertEquals($s,
			$this->wddx->deserialize('<wddxPacket version="1.0"><header/><data><struct><var name="php_class_name"><string>stdClass</string></var><var name="foo"><string>bar</string></var><var name="baz"><string>quux</string></var><var name="a"><array length="2"><string>a</string><string>b</string></array></var></struct></data></wddxPacket>'));
	}

	public function testDeserializeObject()
	{
		$dto = new SerializeDto();
		$this->assertEquals($dto,
			$this->wddx->deserialize('<wddxPacket version="1.0"><header/><data><struct><var name="php_class_name"><string>Mumasoft\WddxShim\SerializeDto</string></var><var name="foo"><string>bar</string></var><var name="baz"><string>quux</string></var></struct></data></wddxPacket>')
		);
	}


}
