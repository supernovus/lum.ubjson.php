<?php

namespace Lum\Encode\UBJSON\Types;

use Lum\Encode\UBJSON\TokenReader;
use Lum\Encode\UBJSON\V;

class Strings extends Type
{
  protected string $token = V::STRING;

  public function getTag(): string
  {
    return $this->token;
  }
  
  public function isValue(&$value): bool
  { // Char and HighPrec must be tested *before* this!
    return (is_string($value));
  }

  public function encode(&$value, $mark=true): string
  {
    $tag = $mark ? $this->token : '';
    $len = strlen($value);
    return $tag . $this->parent->encodeValue($len, true) . $value;
  }

  public function decode(TokenReader $doc, bool $next=true)
  {
    if ($next) $doc->next();
    $len = $this->parent->decodeToken($doc, true);
    $d = $doc->diag();
    $n = json_encode($next);
    $l = json_encode($len);
    #error_log("Strings::decode($d, $n): len=$l");
    if (is_int($len))
    {
      return $doc->read($len);
    }
    return '';
  }
}
