<?php

namespace Lum\Encode\UBJSON\Types;

use Lum\Encode\UBJSON\{V,TokenReader};

class BoolTrue extends Singleton
{
  public function getTag(): string
  {
    return V::TRUE;
  }

  public function isValue(&$value): bool
  {
    return $value === true;
  }

  public function decode(TokenReader $doc, bool $next=true)
  {
    if ($next) $doc->next();
    return true;
  }
}
