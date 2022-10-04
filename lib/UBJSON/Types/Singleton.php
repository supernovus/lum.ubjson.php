<?php

namespace Lum\Encode\UBJSON\Types;

abstract class Singleton extends Type
{
  public function isSingleton() { return true; }

  public function encode(&$value, bool $mark=true): string
  { // Singletons don't care about the value or the mark option.
    return $this->getTag();
  }
}
