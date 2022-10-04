<?php

namespace Lum\Encode\UBJSON\Types;

use Lum\Encode\UBJSON\{V,TokenReader};

class BoolFalse extends Singleton
{
  public function getTag(): string
  {
    return V::FALSE;
  }

  public function isValue(&$value): bool
  {
    return $value === false;
  }

  public function decode(TokenReader $doc, bool $next=true)
  {
    if ($next) $doc->next();
    return false;
  }
}
