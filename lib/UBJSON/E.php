<?php

namespace Lum\Encode\UBJSON;

use Exception;

class E 
{
  const INV_TYPE = "Invalid value type";
  const INV_DATA = "Invalid data format";
  const DATA_SHORT = "Data is too short";
  const OPT_AND_COMPAT = "The optimize and draft9 options are mutually exclusive";
}

class EncodingError extends Exception {}
class DecodingError extends Exception {}
