<?php

namespace Lum\Encode\UBJSON\Types;

use Lum\Encode\UBJSON;

class Integers extends Floats
{
  protected int $min;
  protected int $max;

  public function __construct(UBJSON $parent, 
    int $min, 
    int $max, 
    string $token, 
    string $pack,
    int $len,
    bool $swap)
  {
    parent::__construct($parent, $token, $pack, $len, $swap);
    $this->min = $min;
    $this->max = $max;
  }

  public function isValue(&$value): bool
  {
    return (is_int($value)
      && ($this->min <= $value && $value <= $this->max));
  }

}
