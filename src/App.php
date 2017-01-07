<?php

namespace Fol;

use Interop\Container\ContainerInterface;
use Psr\Http\Message\UriInterface;
use ArrayAccess;
use Throwable;
use Closure;
use Fol\{
    NotFoundException,
    ContainerException,
    ServiceProviderInterface
};

/**
 * Manages an app.
 */
class App implements ContainerInterface, ArrayAccess
{
    private $containers = [];
    private $services = [];
    private $path;
    private $uri;

    /**
     * Constructor. Set the base path and uri
     *
     * @param string $path
     * @param UriInterface $uri
     */
    public function __construct(string $path, UriInterface $uri)
    {
        $this->path = rtrim($path, '/') ?: '/';
        $this->uri = $uri;
    }

    /**
     * Check whether a value exists.
     * 
     * @see ArrayAccess
     * 
     * @param string|int $id
     */
    public function offsetExists($id)
    {
        if (isset($this->services[$id])) {
            return true;
        }

        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a value.
     * 
     * @see ArrayAccess
     * 
     * @param string|int $id
     */
    public function offsetGet($id)
    {
        if (isset($this->services[$id])) {
            $value = $this->services[$id];

            if ($value instanceof Closure) {
                try {
                    return $this->services[$id] = $value($this);
                } catch (Throwable $exception) {
                    throw new ContainerException("Error retrieving {$id}: {$exception->getMessage()}");
                }
            }

            return $value;
        }

        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return $container->get($id);
            }
        }

        throw new NotFoundException("Identifier {$id} is not defined");
    }

    /**
     * Set a new value.
     * 
     * @see ArrayAccess
     * 
     * @param string|int $id
     * @param mixed  $value
     */
    public function offsetSet($id, $value)
    {
        $this->services[$id] = $value;
    }

    /**
     * Removes a value.
     * 
     * @see ArrayAccess
     * 
     * @param string|int $id
     */
    public function offsetUnset($id)
    {
        unset($this->services[$id]);
    }

    /**
     * Register new service provider.
     *
     * @param ServiceProviderInterface $provider
     *
     * @return self
     */
    public function register(ServiceProviderInterface $provider): self
    {
        $provider->register($this);

        return $this;
    }

    /**
     * Add new containers.
     *
     * @param ContainerInterface $container
     *
     * @return self
     */
    public function addContainer(ContainerInterface $container): self
    {
        $this->containers[] = $container;

        return $this;
    }

    /**
     * @see ContainerInterface
     * 
     * {@inheritdoc}
     */
    public function has($id)
    {
        return $this->offsetExists($id);
    }

    /**
     * @see ContainerInterface
     * 
     * {@inheritdoc}
     */
    public function get($id)
    {
        if ($this->offsetExists($id)) {
            return $this->offsetGet($id);
        }

        throw new NotFoundException("Identifier {$id} is not defined");
    }

    /**
     * Set a variable.
     * 
     * @param string|int $id
     * @param mixed  $value
     *
     * @return self
     */
    public function set($id, $value): self
    {
        $this->offsetSet($id, $value);

        return $this;
    }

    /**
     * Returns the absolute path of the app.
     *
     * @param string ...$dirs
     *
     * @return string
     */
    public function getPath(string ...$dirs): string
    {
        if (empty($dirs)) {
            return $this->path;
        }

        return self::canonicalize($this->path, $dirs);
    }

    /*
     * Returns the base uri of the app.
     *
     * @param string ...$dirs
     *
     * @return UriInterface
     */
    public function getUri(string ...$dirs): UriInterface
    {
        if (empty($dirs)) {
            return $this->uri;
        }

        return $this->uri->withPath(self::canonicalize($this->uri->getPath(), $dirs));
    }

    /**
     * helper function to fix paths '//' or '/./' or '/foo/../' in a path.
     *
     * @param string   $base
     * @param string[] $dirs
     *
     * @return string
     */
    private static function canonicalize(string $base, array $dirs): string
    {
        $path = $base.'/'.implode('/', $dirs);
        $replace = ['#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'];

        do {
            $path = preg_replace($replace, '/', $path, -1, $n);
        } while ($n > 0);

        return $path;
    }
}
