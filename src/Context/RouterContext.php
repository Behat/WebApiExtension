<?php

namespace Behat\WebApiExtension\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Context allows using Symfony2 Router
 *
 * @author Łukasz Czarnołęcki <lukasz@czarnolecki.pl
 */
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
     * Generates a url from a given route
     *
     * @param string $route
     * @param array $params
     *
     * @return string
     */
    protected function getUrl($route, $params = [])
    {
        return $this->getRouter()->generate($route, $params, RouterInterface::ABSOLUTE_URL);
    }

    /**
     * Generates a url from a given patch
     *
     * @param $path
     * @return string
     */
    protected function getUrlFromPath($path)
    {
        $info = $this->getRouter()->match($path);
        $route = $info['_route'];
        unset($info['_route']);

        return $this->getUrl($route, $info);
    }
}
