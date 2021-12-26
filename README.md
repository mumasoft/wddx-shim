# PHP >=7.4 WDDX Shim

As WDDX got removed from PHP 7.4 (and moved to PECL), I found the need to have at
least some version of wddx_serialize_value and wddx_deserialize. In this shim this is
somewhat implemented and should result in the same XML data and deserialized values
as with the original functions.

It is very premature, so test it thorougly before using.
