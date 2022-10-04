<?php

namespace Lum\Encode\UBJSON\Types;

use Lum\Encode\UBJSON\TokenReader;
use Lum\Encode\UBJSON\V;

class Chars extends Type
{
  public function getTag(): string
  {
    return V::CHAR;
  }
  
  public function isValue(&$value): bool
  {
    return (is_string($value) && strlen($value) === 1);
  }

  public function encode(&$value, bool $mark=true): string
  {
    $tag = $mark ? V::CHAR : '';
    return $tag . $value;
  }

  public function decode(TokenReader $doc, bool $next=true)
  {
    if ($next) $doc->next();
    return $doc->take();
  }
}
