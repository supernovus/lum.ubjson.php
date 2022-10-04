<?php

namespace Lum\Encode;

use Lum\Encode\UBJSON\{V,N,E,EncodingError,DecodingError, TokenReader};
use Lum\Encode\UBJSON\Types\Type;
use Lum\Encode\UBJSON\Types;
use Lum\Arrays as Arr;

/**
 * My attempt at a UBJSON Draft-12 transcoder.
 * 
 * @package Lum\Encode
 */
class UBJSON
{
  public readonly bool $throwException;
  public readonly bool $decodeAsArray;
  public readonly bool $encodeOptimized;
  public readonly bool $encodeDraft9;

  protected $allTypes = [];
  protected $numTypes = [];
  protected $typesByTag = [];

  public readonly Types\Strings $strType;
  public readonly Types\NoOp $noOp;

  // The following two are not used by the new API at all.
  // There's here for backwards compatibility and that's it.

  const TYPE_ARRAY  = 0;
  const TYPE_OBJECT = 1;

  public static function isLittleEndian()
  {
    return pack('S', 0xFF) === pack('v', 0xFF);
  }

  /**
   * Build a new UBJSON Draft-12 transcoder instance
   * 
   * @param array $opts An associated array of options (Optional);
   * 
   *   [
   *     'throw'     => true,  // Throw Exceptions on errors?
   *     'asArray'   => true,  // Decode objects as associative arrays?
   *     'optimized' => false, // Use optmized containers when encoding?
   *     'draft9'    => false, // Enable compatibility with the old version?  
   *   ]
   * 
   * The `draft9` option is named that not because it specifically
   * implements features from Draft-9, but because it emulates the
   * behaviour of the previous `UBJSON` library that was based on
   * the Draft-9 specification. I don't recommend using that option.
   * 
   * I do recommend the `optimized` option though, as it can make
   * Maps (objects & associative arrays) and Lists (flat arrays) 
   * more efficient both in terms of encoding size, and in terms
   * of decoding speed.
   * 
   * The `optimized` and `draft9` options are mutually exclusive.
   * An exception will be thrown if you try to set both to `true`.
   * 
   * The `asArray` option is only applicable to *decoding*,
   * while the `optimized` and `draft9` options are only
   * applicable to *encoding*. Already encoded objects using
   * any of the supported formats should be decoded properly
   * regardless of the options specified.
   * 
   */
  public function __construct(array $opts=[])
  {
    #error_log("UBJSON::__construct(".json_encode($opts).')');
    $this->setBool($opts, 'throwException',  'throw',     true);
    $this->setBool($opts, 'decodeAsArray',   'asArray',   true);
    $this->setBool($opts, 'encodeOptimized', 'optimized', false);
    $this->setBool($opts, 'encodeDraft9',    'draft9',    false);

    if ($this->encodeDraft9 && $this->encodeOptimized)
    { // This will always throw an exception.
      $msg = E::OPT_AND_COMPAT;
      throw new EncodingError($msg);
    }

    $this->addType(new Types\Maps($this));
    $this->addType(new Types\Lists($this));

    $swap = self::isLittleEndian();

    $intOpts =
    [ // A list of main Integer types to add.
      [       -128,        127, V::INT8,  N::INT8,  1, false],
      [          0,        256, V::UINT8, N::UINT8, 1, false],
      [     -32768,      32767, V::INT16, N::INT16, 2, $swap],
      [-2147483648, 2147483647, V::INT32, N::INT32, 4, $swap],
    ];

    if ($this->encodeDraft9)
    { // The old version had the first two int types swapped.
      Arr::swap($intOpts, 0, 1);
    }

    foreach ($intOpts as $io)
    { // Okay, add them now.
      $this->addType(new Types\Integers($this, ...$io), true);
    }

    // The Int64 has its own class with custom logic.
    $this->addType(new Types\Int64($this, $swap), true);

    $floatOpts = 
    [
      [V::FLOAT,  N::FLOAT,  4, $swap],
      [V::DOUBLE, N::DOUBLE, 8, $swap],
    ];

    foreach ($floatOpts as $fo)
    { // Okay, add them now.
      $this->addType(new Types\Floats($this, ...$fo), true);
    }

    $this->addType(new Types\Chars($this));
    $this->addType(new Types\HighPrec($this));
    $this->strType = $this->addType(new Types\Strings($this));
    $this->addType(new Types\BoolTrue($this));
    $this->addType(new Types\BoolFalse($this));
    $this->addType(new Types\Nullz($this));
    $this->noOp = $this->addType(new Types\NoOp($this));

  }

  protected function addType(Type $type, bool $num=false)
  {
    $this->allTypes[] = $type;
    $this->typesByTag[$type->getTag()] = $type;
    if ($num) $this->numTypes[] = $type;
    return $type;
  }

  protected function setBool(&$opts, string $p, string $o, bool $d)
  {
    #error_log("setBool(".json_encode($opts).", $p, $o, ".json_encode($d).")");
    $this->$p = isset($opts[$o]) ? boolval($opts[$o]) : $d;
    #error_log("setBool() -> ".json_encode($this->$p));
  }

  public function getTypeFor(string $tag): ?Type 
  {
    return $this->typesByTag[$tag] ?? null;
  }

  /**
   * Encode a value
   * 
   * @param mixed &$value Usually an `array` or `object`.
   * @return string A binary UBJSON string.
   * @throws EncodingError 
   */
  public function encodeValue(&$value, bool $numOnly=false): string
  {
    $type = $this->getValueType($value, $numOnly);
    if (!isset($type))
    { // Invalid type.
      $msg = E::INV_TYPE;
      if ($this->throwException)
      {
        throw new EncodingError($msg);
      }
      else 
      {
        error_log($msg);
        return '';
      }
    }
    return $type->encode($value);
  }

  /**
   * Decode a UBJSON-encoded binary string.
   * 
   * @param string $value The binary string.
   * @return mixed 
   * @throws DecodingError 
   */
  public function decodeValue(string $value)
  {
    $doc = new TokenReader($this, $value);
    return $this->decodeToken($doc);
  }

  // The internal entry point for decoding.
  public function decodeToken(TokenReader $doc, bool $numOnly=false)
  {
    $type = $this->getTokenType($doc, $numOnly);
    if (!isset($type))
    { // Invalid type.
      $dinfo = $doc->diag();
      $no = json_encode($numOnly);
      error_log("decodeToken($dinfo, $no)");
      $msg = E::INV_TYPE;
      if ($this->throwException)
      {
        throw new DecodingError($msg);
      }
      else 
      {
        error_log($msg);
        return null;
      }
    }
    return $type->decode($doc);
  }

  // Find the appropriate Type class for a value.
  public function getValueType(&$value, bool $numOnly=false): ?Type 
  {
    $types = $numOnly ? $this->numTypes : $this->allTypes;
    foreach ($types as $type)
    {
      if ($type->isValue($value))
      {
        return $type;
      }
    }
    return null;
  }

  // Find the appropriate Type class for the current token of a document.
  public function getTokenType(TokenReader $doc, bool $numOnly=false): ?Type
  {
    $types = $numOnly ? $this->numTypes : $this->allTypes;
    foreach ($types as $type)
    {
      if ($type->isToken($doc))
      {
        return $type;
      }
    }
    return null;
  }

  /**
   * Encode data using an anonymous instance.
   * 
   * @param mixed $value Value to be passed to `encodeValue()`
   * @param array $opts Constructor options for the new instance.
   * @return string 
   * @throws EncodingError 
   */
  public static function encode($value, array $opts=[]): string 
  {
    $instance = new static($opts);
    return $instance->encodeValue($value);
  }

  /**
   * Decode data using an anonymous instance.
   * 
   * @param string $value Value to be passed to `decodeValue()`
   * @param array $opts Constructor options for the new instance.
   * 
   * @return mixed 
   */
  public static function decode(string $value, $param1=[], $param2=null)
  {
    if (is_array($param1))
    { // The modern API will always use this.
      $opts = $param1;
    }
    elseif (is_int($param1))
    { // The old decode() API from Draft-9 was used.

      $opts = ['draft9'=>true];

      if ($param1 === self::TYPE_OBJECT)
      { 
        $opts['asArray'] = false;
      }
      elseif ($param1 === self::TYPE_ARRAY)
      {
        $opts['asArray'] = true;
      }
      else 
      {
        throw new DecodingError("Unknown decode format type specified");
      }

      if (is_bool($param2))
      {
        $opts['throw'] = $param2;
      }
    }
    else 
    {
      throw new DecodingError("Invalid parameter passed to UBJSON::decode()");
    }

    $instance = new static($opts);
    return $instance->decodeValue($value);
  }

  public static function getLastErrorMessage()
  {
    error_log("getLastErrorMessage() is not used in the new UBJSON library");
    return null;
  }

  public static function cleanLastErrorMessage()
  {
    error_log("cleanLastErrorMessage() is not used in the new UBJSON library");
  }

} // UBJSON class

