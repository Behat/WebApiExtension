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
use Behat\Behat\Gherkin\ServiceContainer\GherkinExtension;
use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Behat\Testwork\Specification\ServiceContainer\SpecificationExtension;
use Behat\Testwork\Suite\ServiceContainer\SuiteExtension;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Symfony2 extension for Behat class.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Extension implements ExtensionInterface {
  const ID = 'web_api';

  /**
   * {@inheritdoc}
   */
  public function getConfigKey() {
    return static::ID;
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(ExtensionManager $extensionManager) {
  }

  /**
   * {@inheritdoc}
   */
  public function configure(ArrayNodeDefinition $builder) {
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
  public function load(ContainerBuilder $container, array $config) {
    $this->loadClient($container, $config);
    $this->loadContextInitializer($container, $config);

    // error_log((new GraphvizDumper($container))->dump(), 3, __DIR__ . '/../testapp/a.dot');
  }

  private function loadClient(ContainerBuilder $container, $config) {
    $definition = new Definition('GuzzleHttp\Client', array($config));
    $container->setDefinition(static::ID . '.client', $definition);
  }

  private function loadContextInitializer(ContainerBuilder $container, $config) {
    $definition = new Definition('Behat\WebApiExtension\Context\Initializer\WebApiAwareInitializer', array(
      new Reference(static::ID . '.client'),
      $config
    ));
    $definition->addTag(ContextExtension::INITIALIZER_TAG, array('priority' => 0));
    $container->setDefinition(static::ID . '.' . ContextExtension::INITIALIZER_TAG, $definition);
  }

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
  }
}
