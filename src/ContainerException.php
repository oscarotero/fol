<?php
declare(strict_types = 1);

namespace Fol;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * Exception throwed by the container on error.
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{
}
