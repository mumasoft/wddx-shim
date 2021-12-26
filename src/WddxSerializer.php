<?php

declare(strict_types=1);

namespace Mumasoft\WddxShim;


use DOMDocument;
use DOMElement;
use DOMNode;
use InvalidArgumentException;
use ReflectionObject;

class WddxSerializer
{
	private DOMDocument $dom;
	private ?DOMNode $root = null;
	private ?DOMNode $header = null;
	private ?DOMElement $comment = null;
	private ?DOMNode $data = null;
	private array $varsSeen = [];

	public function __construct(string $comment = null)
	{
		$this->initDom($comment);
	}

	/**
	 * Actual function for wddx_serialize_value
	 * @param $var
	 * @param string|null $comment
	 * @return false|string
	 */
	public function serializeValue($var, string $comment = null)
	{
		if ($comment !== null)
		{
			$this->addComment($comment);
		}

		$this->addVar($var);

		return $this->getXml();
	}

	/**
	 * Add a variable to the given DOMNode
	 * @param mixed $var The variable to add
	 * @param string|null $varName Optional name of the variable.
	 * @param DOMNode|null $addTo The DOMNode to append the variable to
	 * @return void
	 */
	private function addVar($var, ?string $varName = null, ?DOMNode $addTo = null): void
	{
		if (is_scalar($var) || $var === null)
		{
			$this->addScalar($var, $varName, $addTo);
		}
		else
		{
			$this->addStruct($var, $varName, $addTo);
		}
	}

	private function getNode(DOMNode $node = null, ?string $varName = null)
	{
		if ($node === null)
		{
			$node = $this->data;
		}
		if ($varName !== null)
		{
			$varElem = $this->dom->createElement('var');
			$n = $this->dom->createAttribute('name');
			$n->value = $varName;
			$varElem->appendChild($n);

			$node->appendChild($varElem);
			$node = $varElem;
		}

		return $node;
	}

	private function addScalar($var, ?string $varName = null, DOMNode $node = null)
	{
		$node = $this->getNode($node, $varName);

		if ($var === null)
		{
			$this->addDataElement($node, 'null');
		}
		elseif (is_string($var))
		{
			$this->addDataElement($node, 'string', $var);
		}
		elseif (is_numeric($var))
		{
			$this->addDataElement($node, 'number', $var);
		}
		elseif (is_bool($var))
		{
			$this->addDataElement($node, 'boolean', null, ['value' => $var ? 'true' : 'false']);
		}
		else
		{
			throw new InvalidArgumentException("Unexpected variable type: " . gettype($var));
		}
	}

	function addDataElement(DOMNode $addTo, string $type, $value = null, array $attributes = []): DOMElement
	{
		if ($value !== null)
		{
			$value = strval($value);
		}
		else
		{
			$value = '';
		}

		$addTo->appendChild($elem = $this->dom->createElement($type, $value));
		foreach ($attributes as $attribute => $attributeValue)
		{
			$attr = $this->dom->createAttribute($attribute);
			$attr->value = strval($attributeValue);
			$elem->appendChild($attr);
		}

		return $elem;
	}

	private function addStruct($var, ?string $varName = null, ?DOMNode $node = null)
	{
		$node = $this->getNode($node, $varName);

		if (is_object($var))
		{
			$this->addObject($var, $node);
		}
		elseif (is_array($var))
		{
			$this->addArray($var, $node);
		}
	}

	private function addObject(object $object, DOMNode $node)
	{
		$objectHash = spl_object_hash($object);
		if (in_array($objectHash, $this->varsSeen))
		{
			throw new InvalidArgumentException("Object of type " . get_class($object)
				. " already seen. This probably means a cyclic reference somewhere");
		}
		$this->varsSeen[] = $objectHash;

		$structElem = $this->dom->createElement('struct');
		$node->appendChild($structElem);

		$this->addVar(get_class($object), 'php_class_name', $structElem);

		$r = new ReflectionObject($object);
		foreach ($r->getProperties() as $prop)
		{
			$private = $prop->isPrivate();
			if ($private)
			{
				$prop->setAccessible(true);
			}

			$this->addVar($prop->getValue($object), $prop->getName(), $structElem);

			$prop->setAccessible(!$private);
		}
	}

	/**
	 * @param array $var
	 * @param DOMNode $node
	 * @return void
	 */
	private function addArray(array $var, DOMNode $node): void
	{
		if (function_exists('array_is_list'))
		{
			$isList = array_is_list($var);
		}
		else
		{
			$isList = true;
			foreach (array_keys($var) as $idx)
			{
				if (!is_int($idx))
				{
					$isList = false;
					break;
				}
			}
		}

		if ($isList)
		{
			$this->addArrayList($node, $var);
		}
		else
		{
			$this->addAssociativeArray($node, $var);
		}
	}

	private function addAssociativeArray(DOMNode $addTo, array $array)
	{
		$structElem = $this->addDataElement($addTo, 'struct');
		foreach ($array as $key => $value)
		{
			$this->addVar($value, $key, $structElem);
		}
	}

	private function addArrayList(DOMNode $addTo, array $array)
	{
		$arrayElem = $this->addDataElement($addTo, 'array', null, ['length' => count($array)]);
		foreach ($array as $value)
		{
			$this->addVar($value, null, $arrayElem);
		}
	}

	private function addComment(string $comment): void
	{
		if ($this->comment === null)
		{
			$this->comment = $this->dom->createElement('comment', $comment);
			$this->header->appendChild($this->comment);
		}
		else
		{
			$this->comment->textContent = $comment;
		}
	}

	private function initDom(?string $comment = null): void
	{
		$this->dom = new DOMDocument();
		$this->dom->formatOutput = false;
		$this->dom->preserveWhiteSpace = false;
		$this->dom->encoding = 'utf-8';

		$this->root = $this->dom->createElement('wddxPacket');
		$this->dom->appendChild($this->root);
		$this->root->appendChild($version = $this->dom->createAttribute('version'));
		$version->value = '1.0';

		$this->header = $this->dom->createElement('header');
		$this->root->appendChild($this->header);

		$this->data = $this->dom->createElement('data');
		$this->root->appendChild($this->data);

		if ($comment !== null)
		{
			$this->addComment($comment);
		}
	}

	/**
	 * @return false|string
	 */
	public function getXml()
	{
		if (($xml = $this->dom->saveXML($this->dom)) === null)
		{
			return false;
		}

		static $defaultHeader = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$headerLen = strlen($defaultHeader);
		if (strncmp($defaultHeader, $xml, $headerLen) == 0)
		{
			return trim(substr($xml, $headerLen));
		}

		return trim($xml);
	}
}
