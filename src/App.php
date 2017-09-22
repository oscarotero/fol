<?php

namespace Fol;

use Fol\NotFoundException;
use Fol\ContainerException;
use Psr\Container\ContainerInterface;
use Interop\Container\ServiceProviderInterface;
use Psr\Http\Message\UriInterface;
use Throwable;

/**
 * Manages an app.
 */
class App implements ContainerInterface
{
    private $services = [];
    private $items = [];
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
     * Add a new service provider.
     *
     * @param ServiceProviderInterface $provider
     *
     * @return self
     */
    public function addServiceProvider(ServiceProviderInterface $provider): self
    {
        foreach ($provider->getFactories() as $id => $factory) {
            $this->addFactory($id, $factory);
        }

        foreach ($provider->getExtensions() as $id => $extension) {
            $this->addExtension($id, $extension);
        }

        return $this;
    }

    /**
     * Add a new factory.
     *
     * @param string|int $id
     * @param callable   $factory
     *
     * @return self
     */
    public function addFactory($id, callable $factory): self
    {
        $this->createServiceIfNotExists($id);
        $this->services[$id][0] = $factory;

        return $this;
    }

    /**
     * Add a new extension.
     *
     * @param string|int $id
     * @param callable   $extension
     *
     * @return self
     */
    public function addExtension($id, callable $extension): self
    {
        $this->createServiceIfNotExists($id);
        $this->services[$id][] = $extension;

        return $this;
    }

    /**
     * Add a new factory.
     *
     * @param string|int $id
     * @param callable   $service
     */
    private function createServiceIfNotExists($id)
    {
        if (empty($this->services[$id])) {
            $this->services[$id] = [null];
        }
    }

    /**
     * @see ContainerInterface
     *
     * {@inheritdoc}
     */
    public function has($id)
    {
        if (isset($this->items[$id])) {
            return true;
        }

        if (isset($this->services[$id][0])) {
            return true;
        }

        return false;
    }

    /**
     * @see ContainerInterface
     *
     * {@inheritdoc}
     */
    public function get($id)
    {
        if (isset($this->items[$id])) {
            return $this->items[$id];
        }

        if (isset($this->services[$id][0])) {
            try {
                return $this->items[$id] = array_reduce(
                    $this->services[$id],
                    function ($item, $callback) {
                        return $callback($this, $item);
                    }
                );
            } catch (Throwable $exception) {
                throw new ContainerException("Error retrieving {$id}", 0, $exception);
            }
        }

        throw new NotFoundException("Identifier {$id} is not found");
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
        $this->items[$id] = $value;

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
