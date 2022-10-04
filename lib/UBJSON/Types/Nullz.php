<?php

namespace Lum\Encode\UBJSON\Types;

use Lum\Encode\UBJSON\TokenReader;
use Lum\Encode\UBJSON\V;

class Nullz extends Singleton
{
  public function getTag(): string
  {
    return V::NULL;
  }
  
  public function isValue(&$value): bool
  {
    return is_null($value);
  }

  public function decode(TokenReader $doc, bool $next=true)
  {
    if ($next) $doc->next();
    return null;
  }
}
