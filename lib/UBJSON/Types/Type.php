<?php

namespace Lum\Encode\UBJSON\Types;

use Lum\Encode\UBJSON;
use Lum\Encode\UBJSON\TokenReader;

/**
 * Abstract class for encoders.
 */
abstract class Type 
{
  protected UBJSON $parent;
  
  public function __construct(UBJSON $parent)
  {
    $this->parent = $parent;
  }

  abstract function getTag(): string;

  abstract function isValue(&$value): bool;
  abstract function encode(&$value, bool $mark=true): string;
  abstract function decode(TokenReader $doc, bool $next=true);

  public function isToken(TokenReader $doc): bool
  {
    $tag = $this->getTag();
    $token = $doc->peek();
    return ($token === $tag);
  }

  public function isSingleton() { return false; }
}
