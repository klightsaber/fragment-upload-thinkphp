<?php

namespace klightsaber\exception;

use think\Exception;
use Throwable;


class FragmentUploadException extends Exception
{
   public function __construct(int $code = 0, string $message = "",Throwable $previous = null)
   {
       parent::__construct($message, $code, $previous);
   }
}
