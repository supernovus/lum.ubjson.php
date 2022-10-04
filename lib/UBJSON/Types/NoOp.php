<?php

namespace Lum\Encode\UBJSON\Types;

use Lum\Encode\UBJSON\TokenReader;
use Lum\Encode\UBJSON\V;

class NoOp extends Singleton
{
  public function getTag(): string
  {
    return V::NOOP;
  }

  public function isValue(&$value): bool
  {
    return $value === $this;
  }

  public function decode(TokenReader $doc, bool $next=true)
  {
    if ($next) $doc->next();
    return $this;
  }
}
