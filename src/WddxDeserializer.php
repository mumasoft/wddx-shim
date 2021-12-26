<?php

declare(strict_types=1);

namespace Mumasoft\WddxShim;

use DOMDocument;
use DOMNamedNodeMap;
use DOMNode;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use ReflectionClass;

class WddxDeserializer
{
	private ?DOMDocument $dom = null;

	/**
	 * Deserialize a WDDX packet
	 * @param string $packet The WDDX packet to deserialize
	 * @return mixed
	 */
	public function deserialize(string $packet)
	{
		try
		{
			$this->dom = new DOMDocument();
			$this->dom->loadXML($packet);

			$xpath = new DOMXPath($this->dom);
			$dataNode = $xpath->query('/wddxPacket/data');
			$numChildNodes = $dataNode->count();
			if ($numChildNodes == 0)
			{
				return null;
			}
			elseif ($numChildNodes > 1)
			{
				throw new InvalidArgumentException("Invalid number of child nodes in data node. " .
					"Found $numChildNodes, expected 1");
			}

			return $this->deserializeNode($dataNode->item(0)->firstChild);

		}
		finally
		{
			$this->dom = null;
		}
	}

	private function deserializeNode(DOMNode $dataNode)
	{
		$firstChild = $dataNode;
		$value = $firstChild->nodeValue;
		switch ($firstChild->localName)
		{
			case 'null':
				return null;
			case 'string':
				return strval($value);
			case 'boolean':
				/** @var DOMNamedNodeMap $attributes */
				$attributes = $firstChild->attributes;
				if (($bv = $attributes->getNamedItem('value')) === null)
				{
					return null;
				}

				return $bv->nodeValue === 'true';
			case 'number':
				return strstr($value, '.') ? floatval($value) : intval($value);
			case 'array':
				return $this->deserializeArray($firstChild);
			case 'struct':
				return $this->deserializeStruct($firstChild);
		}

		return null;
	}

	private function deserializeArray(DOMNode $arrayNode): array
	{
		$len = null;
		if (($len = $arrayNode->attributes->getNamedItem('length')) !== null)
		{
			$len = intval($len->nodeValue);
		}

		$array = [];
		foreach ($arrayNode->childNodes as $childNode)
		{
			$array[] = $this->deserializeNode($childNode);
		}

		assert($len == count($array));

		return $array;
	}

	private function deserializeStruct(DOMNode $structNode)
	{
		// Determine if we need to deserialize an associative array or an object.
		/** @var DOMNode $varNode */
		$varNode = $structNode->firstChild;
		if ($varNode === null || $varNode->nodeName !== 'var')
		{
			return null;
		}
		if (($varName = $varNode->attributes->getNamedItem('name')) === null)
		{
			return null;
		}
		if ($varName->nodeValue === 'php_class_name')
		{
			return $this->deserializeObject($structNode, $varNode->nodeValue);
		}

		return $this->deserializeAssociativeArray($structNode);
	}

	private function deserializeObject(DOMNode $structNode, string $class)
	{
		if (!class_exists($class))
		{
			throw new \Exception("Class $class does not exist. Cannot deserialize into it");
		}

		$r = new ReflectionClass($class);
		$dest = $r->newInstanceWithoutConstructor();
		$count = 0;
		foreach ($structNode->childNodes as $childNode)
		{
			// Skip the first entry as it contains the php_class_name var.
			if ($count++ == 0)
			{
				continue;
			}

			if (($varAndValue = $this->getVar($childNode)) === null)
			{
				continue;
			}

			[$varName, $varValue] = $varAndValue;
			if ($dest instanceof \stdClass)
			{
				$dest->$varName = $varValue;
			}
			else
			{
				try
				{
					$prop = $r->getProperty($varName);
					$private = $prop->isPrivate();
					if ($private)
					{
						$prop->setAccessible(true);
					}
					$prop->setValue($dest, $varValue);
					if ($private)
					{
						$prop->setAccessible(false);
					}
				}
				catch (\ReflectionException $e)
				{
					// Fallback if the property doesn't exist on the class.
					$dest->$varName = $varValue;
				}
			}

		}

		return $dest;
	}

	private function deserializeAssociativeArray(DOMNode $structNode): array
	{
		$array = [];
		foreach ($structNode->childNodes as $childNode)
		{
			if (($varAndValue = $this->getVar($childNode)) === null)
			{
				continue;
			}

			$array[$varAndValue[0]] = $varAndValue[1];
		}

		return $array;
	}

	private function getVar(DOMNode $node): ?array
	{
		if (($name = $node->attributes->getNamedItem('name')) === null || $node->localName !== 'var' ||
			$node->firstChild === null)
		{
			return null;
		}

		return [$name->nodeValue, $this->deserializeNode($node->firstChild)];
	}
}
