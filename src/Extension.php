<?php

/*
 * This file is part of the Behat Symfony2Extension
 *
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Behat\WebApiExtension;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Symfony2 extension for Behat class.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Extension implements ExtensionInterface
{
    const CLIENT_ID = 'web_api.client';

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
                ->scalarNode('base_url')
                    ->defaultValue('http://localhost')
                    ->end()
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $this->loadClient($container, $config);
        $this->loadContextInitializer($container, $config);
    }

    private function loadClient(ContainerBuilder $container, $config)
    {
        $definition = new Definition('GuzzleHttp\Client', array($config));
        $container->setDefinition(self::CLIENT_ID, $definition);
    }

    private function loadContextInitializer(ContainerBuilder $container, $config)
    {
        $definition = new Definition('Behat\WebApiExtension\Context\Initializer\WebApiAwareInitializer', array(
          new Reference(self::CLIENT_ID),
          $config
        ));
        $definition->addTag(ContextExtension::INITIALIZER_TAG);
        $container->setDefinition('web_api.context_initializer', $definition);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
    }
}
