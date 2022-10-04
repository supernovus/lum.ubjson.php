<?php

namespace Lum\Encode\UBJSON;

/**
 * An internal static class of encoder values.
 */
class V
{
  const NOOP   = 'N';
  const NULL   = 'Z';
  const FALSE  = 'F';
  const TRUE   = 'T';
  const INT8   = 'i';
  const UINT8  = 'U';
  const INT16  = 'I';
  const INT32  = 'l';
  const INT64  = 'L';
  const FLOAT  = 'd';
  const DOUBLE = 'D';
  const CHAR   = 'C';
  const STRING = 'S';
  const HIGH_PRECISION = 'H';
  const ARRAY_OPEN	 = '[';
  const ARRAY_CLOSE	 = ']';
  const OBJECT_OPEN	 = '{';
  const OBJECT_CLOSE	 = '}';
  const CONTAINER_TYPE = '$';
  const CONTAINER_COUNT = '#';
}
