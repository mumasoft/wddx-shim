<?php
/** @noinspection PhpUnusedPrivateFieldInspection */
/** @noinspection PhpMissingFieldTypeInspection */

declare(strict_types=1);

namespace Mumasoft\WddxShim;

/**
 * Test class to test WDDX (de)serialization
 * @property SerializeDto $self
 */
class SerializeDto
{
	private $foo = 'bar';
	private $baz = 'quux';

	/**
	 * @param SerializeDto $self
	 */
	public function setSelf(SerializeDto $self): void
	{
		$this->self = $self;
	}
}
