<?php

namespace Lum\Encode\UBJSON\Types;

use Lum\Encode\UBJSON\V;

class HighPrec extends Strings
{
  protected string $token = V::HIGH_PRECISION;
  public function isValue(&$value): bool
  { 
    return (is_string($value) && preg_match('/^[\d]+(:?\.[\d]+)?$/', $value));
  }
}
