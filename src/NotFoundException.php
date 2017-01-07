<?php

namespace Fol;

use Exception;
use Interop\Container\Exception\NotFoundException as NotFoundExceptionInterface;

/**
 * Exception throwed by the container when the item is not found.
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
