<?php

namespace Ekyna\Bundle\CharacteristicsBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class EkynaCharacteristicsExtension
 * @package Ekyna\Bundle\CharacteristicsBundle\DependencyInjection
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class EkynaCharacteristicsExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        // Characteristics classes map (CTI)
        $container->setParameter('ekyna_characteristics.characteristics_classes_map', $config['classes']);

        $bundles = $container->getParameter('kernel.bundles');

        // Metadata
        if ('none' === $config['metadata']['cache']) {
            $container->removeAlias('ekyna_characteristics.metadata.cache');
        } elseif ('file' === $config['metadata']['cache']) {
            $container
                ->getDefinition('ekyna_characteristics.metadata.cache.file_cache')
                ->replaceArgument(0, $config['metadata']['file_cache']['dir'])
            ;

            $dir = $container->getParameterBag()->resolveValue($config['metadata']['file_cache']['dir']);
            if (!file_exists($dir)) {
                if (!$rs = @mkdir($dir, 0777, true)) {
                    throw new \RuntimeException(sprintf('Could not create cache directory "%s".', $dir));
                }
            }
        } else {
            $container->setAlias('ekyna_characteristics.metadata.cache', new Alias($config['metadata']['cache'], false));
        }
        $container
            ->getDefinition('ekyna_characteristics.metadata_factory')
            ->replaceArgument(2, $config['metadata']['debug'])
        ;

        // Directories
        $metadataDirectories = $schemaDirectories = array();
        $autoMetadata = $config['metadata']['auto_detection'];
        $autoSchema   = $config['schema']['auto_detection'];

        if ($autoMetadata || $autoSchema) {
            foreach ($bundles as $name => $class) {
                $ref = new \ReflectionClass($class);

                if (is_dir($dir = dirname($ref->getFileName()).'/Resources/config/characteristics')) {
                    if($autoMetadata) {
                        $metadataDirectories[$ref->getNamespaceName()] = $dir;
                    }
                    if($autoSchema) {
                        $schemaDirectories[] = $dir;
                    }
                }
            }
        }

        // Metadata directories
        foreach ($config['metadata']['directories'] as $directory) {
            $directory['path'] = rtrim(str_replace('\\', '/', $directory['path']), '/');

            if ('@' === $directory['path'][0]) {
                $bundleName = substr($directory['path'], 1, strpos($directory['path'], '/') - 1);

                if (!isset($bundles[$bundleName])) {
                    throw new \RuntimeException(sprintf('The bundle "%s" has not been registered with AppKernel. Available bundles: %s', $bundleName, implode(', ', array_keys($bundles))));
                }

                $ref = new \ReflectionClass($bundles[$bundleName]);
                $directory['path'] = dirname($ref->getFileName()).substr($directory['path'], strlen('@'.$bundleName));
            }

            $metadataDirectories[rtrim($directory['namespace_prefix'], '\\')] = rtrim($directory['path'], '\\/');
        }
        $container
            ->getDefinition('ekyna_characteristics.metadata.file_locator')
            ->replaceArgument(0, $metadataDirectories)
        ;

        // Schema directories
        foreach ($config['schema']['directories'] as $directory) {
            $directory = rtrim(str_replace('\\', '/', $directory), '/');

            if ('@' === $directory[0]) {
                $bundleName = substr($directory, 1, strpos($directory, '/') - 1);

                if (!isset($bundles[$bundleName])) {
                    throw new \RuntimeException(sprintf('The bundle "%s" has not been registered with AppKernel. Available bundles: %s', $bundleName, implode(', ', array_keys($bundles))));
                }

                $ref = new \ReflectionClass($bundles[$bundleName]);
                $directory = dirname($ref->getFileName()).substr($directory, strlen('@'.$bundleName));
            }

            $directory = rtrim($directory, '\\/');
            if (! in_array($directory, $schemaDirectories)) {
                $schemaDirectories[] = $directory;
            }
        }
        $container
            ->getDefinition('ekyna_characteristics.schema_registry')
            ->replaceArgument(0, $schemaDirectories)
        ;
    }
}
