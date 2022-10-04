<?php

namespace Lum\Encode\UBJSON;

use Lum\Encode\UBJSON;

/**
 * An internal class for reading a UBJSON document
 * and parsing the tokens from it.
 */
class TokenReader
{
  protected UBJSON $parent;
  protected string $source;

  protected int $pos = 0;
  protected int $len = 0;

  public function __construct(UBJSON $parent, string $source)
  {
    $this->parent = $parent;
    $this->source = $source;
    $this->len = strlen($source);
  }
  
  public function diag(int $look=1, bool $src=false): string 
  {
    $info = ["pos"=>$this->pos, "len"=>$this->len];

    if ($look === 1) $info["cur"] = $this->peek();
    elseif ($look > 1) $info["cur"] = $this->read($look, false, false);

    if ($src) $info["src"] = $this->source;
    
    return json_encode($info);
  }

  /**
   * Look at the current one-byte character.
   * 
   * Does not increment the current position marker.
   * 
   * @return null|string 
   */
  public function peek(): ?string
  {
    return $this->source[$this->pos] ?? null;
  }

  /**
   * Increment the current position marker.
   * 
   * @param int $by Number of bytes to increment by
   *                (Optional, default: `1`);
   * @return void 
   */
  public function next(int $by=1)
  {
    $this->pos += $by;
  }

  /**
   * Get the current one-byte character.
   * 
   * Increments the current position by one.
   * 
   * @return null|string 
   */
  public function take(): ?string
  {
    $token = $this->peek();
    $this->next();
    return $token;
  }

  /**
   * Read a number of bytes from the encoded source.
   * 
   * @param int $bytes Number of bytes to read.
   * @param bool $inc  Increment the current position?
   *                   (Optional, default: `true`);
   * @return string 
   */
  public function read(int $bytes, bool $inc=true, bool $exact=true): string
  {
    if ($exact && !$this->left($bytes))
    {
      $p = $this->pos;
      $l = $this->len;
      #error_log("read($bytes):$p/$l");
      $msg = E::DATA_SHORT;
      if ($this->parent->throwException)
      {
        throw new DecodingError($msg);
      }
      else 
      {
        error_log($msg);
        return '';
      }
    }

    $chunk = substr($this->source, $this->pos, $bytes);
    if ($inc)
    {
      $this->next($bytes);
    }
    return $chunk;
  }

  /**
   * Unpack a packed number.
   * 
   * @param string $flag The `pack` format code.
   * @param int $bytes The number of bytes to read.
   * @param bool $swap If the encoded value is swapped.
   * @return int|float|null The decoded value, or `null` if invalid.
   */
  public function unpack(string $flag, int $bytes, bool $swap)
  {
    #error_log("Doc<".$this->diag().">::unpack($flag, $bytes, ".json_encode($swap).")");
    $packed = $this->read($bytes);
    #error_log("Doc<".$this->diag().">::unpack -> packed=".json_encode($packed));
    if (empty($packed)) return null;
    if ($swap)
    {
      $packed = strrev($packed);
    }

    [,$value] = unpack($flag, $packed);
    return $value;
  }

  /**
   * Is there at least *x* number of bytes left?
   * 
   * @param int $bytes Number of bytes wanted.
   * @return bool 
   */
  public function left($bytes=1): bool
  {
    return ($this->len >= $this->pos + $bytes);
  }

  /**
   * Have we reached the end of the document?
   * 
   * @return bool 
   */
  public function EOF(): bool
  {
    return $this->pos >= $this->len;
  }

}
