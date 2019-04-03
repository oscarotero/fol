<?php
declare(strict_types = 1);

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

    public function __construct(string $path, UriInterface $uri)
    {
        $this->path = rtrim($path, '/') ?: '/';
        $this->uri = $uri;
    }

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

    public function addFactory(string $id, callable $factory): self
    {
        $service = $this->services[$id] ?? [];
        $service[0] = $factory;
        $this->services[$id] = $service;

        return $this;
    }

    public function addExtension($id, callable $extension): self
    {
        $service = $this->services[$id] ?? [null];
        $service[] = $extension;
        $this->services[$id] = $service;

        return $this;
    }

    /**
     * @see ContainerInterface
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
                    function ($item, callable $callback) {
                        return $callback($this, $item);
                    }
                );
            } catch (Throwable $exception) {
                throw new ContainerException("Error retrieving {$id}", 0, $exception);
            }
        }

        throw new NotFoundException("Identifier {$id} is not found");
    }

    public function set(string $id, $value): self
    {
        $this->items[$id] = $value;

        return $this;
    }

    /**
     * Returns the absolute path of the app.
     */
    public function getPath(string ...$dirs): string
    {
        if (empty($dirs)) {
            return $this->path;
        }

        return self::canonicalize($this->path, ...$dirs);
    }

    /*
     * Returns the base uri of the app.
     */
    public function getUri(string ...$dirs): UriInterface
    {
        if (empty($dirs)) {
            return $this->uri;
        }

        return $this->uri->withPath(self::canonicalize($this->uri->getPath(), ...$dirs));
    }

    /**
     * helper function to fix paths '//' or '/./' or '/foo/../' in a path.
     */
    private static function canonicalize(string $base, string ...$dirs): string
    {
        $path = $base.'/'.implode('/', $dirs);
        $replace = ['#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'];

        do {
            $path = preg_replace($replace, '/', $path, -1, $n);
        } while ($n > 0);

        return $path;
    }
}
