<?php

namespace Fol;

use Exception;
use Interop\Container\Exception\ContainerException as ContainerExceptionInterface;

/**
 * Exception throwed by the container on error.
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{
}
