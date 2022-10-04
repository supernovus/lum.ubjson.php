<?php

namespace Lum\Encode\UBJSON\Types;

use Lum\Encode\UBJSON;
use Lum\Encode\UBJSON\TokenReader;

class Floats extends Type
{
  protected string $token;
  protected string $pack;
  protected int $len;
  protected bool $swap;

  public function __construct(UBJSON $parent, 
    string $token, 
    string $pack,
    int $len,
    bool $swap)
  {
    parent::__construct($parent);
    $this->token = $token;
    $this->pack = $pack;
    $this->len = $len;
    $this->swap = $swap;
  }

  public function getTag(): string
  {
    return $this->token;
  }

  public function isValue(&$value): bool
  {
    if (!is_float($value)) return false;
    $pk = $this->pack;
    $f32 = pack($pk, $value);
    [,$test] = unpack($pk, $f32);
    return ($value === $test);
  }

  public function encode(&$value, bool $mark=true): string
  {
    $tag = $mark ? $this->token : '';
    $packed = pack($this->pack, $value);
    if ($this->swap)
    {
      $packed = strrev($packed);
    }
    return $tag . $packed;
  }

  public function decode(TokenReader $doc, bool $next=true)
  {
    if ($next) $doc->next();
    return $doc->unpack($this->pack, $this->len, $this->swap);
  }

}
