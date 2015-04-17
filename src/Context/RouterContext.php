<?php

namespace Behat\WebApiExtension\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

class RouterContext implements KernelAwareContext
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * Sets Kernel instance.
     *
     * @param KernelInterface $kernel
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return RouterInterface
     */
    private function getRouter()
    {
        if (null === $this->router) {
            $this->kernel->boot();
            $this->router = $this->kernel->getContainer()->get('router');
        }

        return $this->router;
    }

    /**
     * @param string $route
     * @param array  $params
     *
     * @return string
     */
    protected function getRoute($route, $params = [])
    {
        return $this->getRouter()->generate($route, $params, RouterInterface::ABSOLUTE_URL);
    }
}
