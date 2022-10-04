<?php

namespace Lum\Encode\UBJSON\Types;

use Lum\Encode\UBJSON\DecodingError;
use Lum\Encode\UBJSON\TokenReader;
use Lum\Encode\UBJSON\{V,E};

abstract class Container extends Type
{
  public function findValueType(array &$array): ?Type
  {
    $ftype = null;

    foreach ($array as $val)
    {
      if (is_null($ftype))
      { // Set the ftype.
        $ftype = $this->parent->getValueType($val);
        if (is_null($ftype))
        { // Still none, cannot continue.
          return null;
        }
      }
      elseif (!$ftype->isValue($val))
      { // A type did not match.
        return null;
      }
    }

    // If we made it here, a common type was found.
    return $ftype;
  }

  public function findTokenType(TokenReader $doc): array
  {
    $type  = null;
    $count = null;
    $sval  = null;
    $hval  = false;

    $tag = $doc->peek();

    if ($tag === V::CONTAINER_TYPE)
    {
      $doc->next();
      $tag = $doc->take();
      $type = $this->parent->getTypeFor($tag);
      $tag = $doc->peek();
      if (isset($type) && $type->isSingleton())
      { // Get the singleton value now.
        $hval = true;
        $sval = $type->decode($doc, false);
      }
    }

    if ($tag === V::CONTAINER_COUNT)
    {
      $doc->next();
      $count = $this->parent->decodeToken($doc, true);
    }

    if (isset($type) && !isset($count))
    { // If type is set, count must also be set.
      $msg = E::INV_DATA;
      throw new DecodingError($msg);
    }

    return [$type, $count, $hval, $sval];
  }
}
