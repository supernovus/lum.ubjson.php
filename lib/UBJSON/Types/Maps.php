<?php

namespace Lum\Encode\UBJSON\Types;

use Iterator;
use Lum\Encode\UBJSON\TokenReader;
use Lum\Encode\UBJSON\V;
use Lum\Arrays as Arr;

class Maps extends Container
{
  public function getTag(): string
  {
    return V::OBJECT_OPEN;
  }

  public function isValue(&$value): bool
  { 
    if (Arr::is_assoc($value))
    { // An associative array.
      return true;
    }
    elseif (is_object($value) && !($value instanceof Iterator))
    { // It's a non-Iterator object.
      return true;
    }
    return false;
  }

  public function encode(&$value, bool $mark=true): string
  {
    if (is_object($value))
    { // We don't work on the object itself.
      return $this->encode(get_object_vars($value));
    }

    // Draft-12 does not mark the key type, draft-9 did.
    $markKey = $this->parent->encodeDraft9;
    $optimize = $this->parent->encodeOptimized;
    $ctype = null;

    $result = V::OBJECT_OPEN;

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

    foreach ($value as $key => $val)
    {
      $result .= $this->parent->strType->encode($key, $markKey);
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

    if (!$optimize) $result .= V::OBJECT_CLOSE;

    return $result;
  }

  public function decode(TokenReader $doc, bool $next=true)
  {
    if ($next) $doc->next();

    [$ctype, $count, $hval, $sval] = $this->findTokenType($doc);

    $asar = $this->parent->decodeAsArray;
    $result = $asar ? [] : new \stdClass();

    #$diag = $doc->diag();
    #$dinfo = json_encode([$ctype, $count, $hval, $sval]);
    #error_log("Maps::decode($diag) tokenType=$dinfo");

    $i = 0;
    while(true)
    {
      #$diag = $doc->diag();
      #error_log("Lists::decode($diag) i=$i");
      if ($doc->peek() === V::OBJECT_CLOSE)
      { // An explicit end of object token.
        $doc->next();
        break;
      }
      elseif ((!$hval && $doc->EOF()) || (isset($count) && $i >= $count))
      { // We're done here.
        break;
      }
      elseif ($doc->peek() === V::STRING)
      { // Key type is not used in Draft-12.
        $doc->next();
      }

      $key = $this->parent->strType->decode($doc, false);

      #$di = $doc->diag();
      #$nx = json_encode($next);
      #$k = json_encode($key);
      #error_log("Maps::decode($di, $nx): key=$k");

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

      if ($asar)
      {
        $result[$key] = $value;
      }
      else
      {
        $result->$key = $value;
      }

      $i++;
    }

    return $result;
  }

}
