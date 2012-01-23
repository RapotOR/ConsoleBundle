<?php

namespace Sf2gen\Bundle\ConsoleBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Finder\Finder;

class Sf2genConsoleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        if ($config['toolbar']) {
            $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
            $loader->load('toolbar.yml');
        }

        $local = basename($container->getParameter('kernel.root_dir'));

        if ($config['all']) {
            $config['apps'] = array_merge($config['apps'], $this->getApps($container));
        }

        $config['apps'] = array_unique($config['apps']);

        if ($config['local'] && array_search($local, $config['apps']) === false) {
            $config['apps'][] = $local;
        }

        $container->setParameter('sf2gen_console.apps', $config['apps']);
        $container->setParameter('sf2gen_console.new_process', $config['new_process']);
    }

    public function getApps(ContainerBuilder $container)
    {
        $apps = array();

        $finder = new Finder();
        $finder->files()
               ->depth('== 1')
               ->name('console')
               ->in($container->getParameter('kernel.root_dir') . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);

        foreach ($finder as $file) {
            $apps[] = $file->getRelativePath();
        }

        return $apps;
    }

    public function getAlias()
    {
        return 'sf2gen_console';
    }
}
