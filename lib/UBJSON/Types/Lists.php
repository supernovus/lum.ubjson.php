<?php

namespace Lum\Encode\UBJSON\Types;

use Iterator;
use Lum\Encode\UBJSON\TokenReader;
use Lum\Encode\UBJSON\V;
use Lum\Arrays as Arr;

class Lists extends Container
{
  public function getTag(): string
  {
    return V::ARRAY_OPEN;
  }

  public function isValue(&$value): bool
  { 
    if (Arr::is_flat($value))
    { // A flat array.
      return true;
    }
    elseif (is_object($value) && $value instanceof Iterator)
    { // It's an Iterator object.
      return true;
    }
    return false;
  }

  public function encode(&$value, bool $mark=true): string
  {
    if (is_object($value))
    { // We don't work on the object itself.
      return $this->encode((array)$value);
    }

    $optimize = $this->parent->encodeOptimized;
    $ctype = null;

    $result = V::ARRAY_OPEN;

    if ($optimize)
    {
      $ctype = $this->findValueType($value);
      if (isset($ctype))
      {
        $result .= V::CONTAINER_TYPE . $ctype->getTag();
      }
      $result .= V::CONTAINER_COUNT;
      $len = count($value);
      $result .= $this->parent->encodeValue($len, true);
    }

    foreach ($value as $val)
    {
      if (isset($ctype))
      {
        if (!$ctype->isSingleton())
        {
          $result .= $ctype->encode($val, false);
        }
      }
      else 
      {
        $result .= $this->parent->encodeValue($val);
      }
    }

    if (!$optimize) $result .= V::ARRAY_CLOSE;

    return $result;
  }

  public function decode(TokenReader $doc, bool $next=true)
  {
    if ($next) $doc->next();

    [$ctype, $count, $hval, $sval] = $this->findTokenType($doc);

    #$diag = $doc->diag();
    #$dinfo = json_encode([$ctype, $count, $hval, $sval]);
    #error_log("Lists::decode($diag) tokenType=$dinfo");

    $result = [];

    $i = 0;
    while(true)
    {
      #$diag = $doc->diag();
      #error_log("Lists::decode($diag) i=$i");
      if ($doc->peek() === V::ARRAY_CLOSE)
      { // An explicit end of array token.
        $doc->next();
        break;
      }
      elseif ((!$hval && $doc->EOF()) || (isset($count) && $i >= $count))
      { // We're done here.
        break;
      }

      if ($hval)
      {
        $value = $sval;
      }
      elseif (isset($ctype))
      {
        $value = $ctype->decode($doc, false);
      }
      else
      {
        $value = $this->parent->decodeToken($doc);
      }

      $result[] = $value;

      $i++;
    }

    return $result;
  }

}
