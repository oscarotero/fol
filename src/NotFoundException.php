<?php
declare(strict_types = 1);

namespace Fol;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception throwed by the container when the item is not found.
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
