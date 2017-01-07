<?php

namespace Fol;

/**
 * Interface used to register services.
 */
interface ServiceProviderInterface
{
    /**
     * Register the service.
     */
    public function register(App $app);
}
