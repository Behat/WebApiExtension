<?php

/*
 * This file is part of the Behat WebApiExtension.
 *
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\WebApiExtension\ServiceContainer;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Behat\WebApiExtension\Context\Initializer\ApiClientContextInitializer;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * Web API extension for Behat.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class WebApiExtension implements ExtensionInterface
{
    const CLIENT_ID = 'web_api.client';

    const CONTEXT_INITIALIZER_ID = 'web_api.context_initializer';

    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'web_api';
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('base_uri')->defaultValue('http://127.0.0.1:8000')->end()
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $this->loadClient($container);
        $this->loadContextInitializer($container, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
    }

    private function loadClient(ContainerBuilder $container)
    {
        $definition = new Definition(Psr18Client::class);

        $container->setDefinition(self::CLIENT_ID, $definition);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function loadContextInitializer(ContainerBuilder $container, array $config)
    {
        $definition = new Definition(ApiClientContextInitializer::class, [
            new Reference(self::CLIENT_ID),
            $config,
        ]);
        $definition->addTag(ContextExtension::INITIALIZER_TAG);

        $container->setDefinition(self::CONTEXT_INITIALIZER_ID, $definition);
    }
}
