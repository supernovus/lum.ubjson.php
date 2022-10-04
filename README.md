# lum.ubjson.php

## Summary

A [UBJSON](https://ubjson.org/) (draft-12) implementation.

### Classes

| Name                    | Description                                       |
| ----------------------- | ------------------------------------------------- |
| Lum\Encode\UBJSON       | The main transcoder class.                        |

### Example Usage

#### Quick with defaults

```php
use Lum\Encode\UBJSON;

$encodedString = UBJSON::encode($anArrayOrObject);

$decodedArray = UBJSON::decode($encodedString);
```

#### With extra options

```php
use Lum\Encode\UBJSON;

// Default option values shown below.
// Only include the ones you want to override.
// 'optimized' and 'draft9' are mutually exclusive.
$ubj = new UBJSON(
[
  'throw'     => true,
  'asArray'   => true,
  'optimized' => false,
  'draft9'    => false,
]);

$encodedString = $ubj->encodeValue($arrayOrObject);

$decodedArrayOrObject = $ubj->decodeValue($encodedString);
```

The `draft9` option is kept only for compatibility 
with the previous UBJSON implementation that lived in the 
`lum-encode` package. It should not be used in new code.

## Official URLs

This library can be found in two places:

 * [Github](https://github.com/supernovus/lum.ubjson.php)
 * [Packageist](https://packagist.org/packages/lum/lum-ubjson)

## Authors

- Timothy Totten

## License

[MIT](https://spdx.org/licenses/MIT.html)
