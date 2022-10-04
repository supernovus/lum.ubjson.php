<?php

namespace Lum\Encode\UBJSON\Types;

use Lum\Encode\UBJSON;
use Lum\Encode\UBJSON\{V,N};

class Int64 extends Floats
{
  const lower = -2147483648;
  const upper = 2147483647;

  public function __construct(UBJSON $parent, bool $swap)
  {
    parent::__construct($parent, V::INT64, N::INT64, 8, $swap);
  }

  public function isValue(&$value): bool
  {
    return (is_int($value)
      && ($value < self::lower || $value > self::upper));
  }

}
