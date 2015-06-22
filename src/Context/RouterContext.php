<?php

namespace Behat\WebApiExtension\Context;

use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RequestContext;
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
    protected $kernel;

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
     * @param $method
     * @return string
     */
    protected function getUrlFromPath($path, $method = 'GET')
    {
        try {
            $baseUrl = $this->kernel->getContainer()->getParameter('router.request_context.base_url');
        } catch (InvalidArgumentException $e) {
            $baseUrl = '';
        }

        try {
            $host = $this->kernel->getContainer()->getParameter('router.request_context.host');
        } catch (InvalidArgumentException $e) {
            $host = 'localhost';
        }

        $requestContext = new RequestContext($baseUrl, $method, $host);
        $router         = $this->getRouter();
        $router->setContext($requestContext);

        $urlStack = parse_url($path);
        $info     = $router->match($urlStack['path']);
        $route    = $info['_route'];
        unset($info['_route']);

        $url = $this->getUrl($route, $info);

        if (isset($urlStack['query'])) {
            $url .= '?' . $urlStack['query'];
        }

        return $url;
    }
}
