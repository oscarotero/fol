<?php

namespace Fol;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception throwed by the container when the item is not found.
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
