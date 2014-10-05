<?php

namespace Ekyna\Bundle\CharacteristicsBundle;

use Ekyna\Bundle\CharacteristicsBundle\DependencyInjection\Compiler\AdminMenuPass;
use Ekyna\Component\Characteristics\DependencyInjection\Compiler\TwigPathCompilerPass;
use Ekyna\Component\Characteristics\Doctrine\ORM\DoctrineOrmMapping;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class EkynaCharacteristicsBundle
 * @package Ekyna\Bundle\CharacteristicsBundle
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class EkynaCharacteristicsBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $ormCompilerClass = 'Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass';
        if (class_exists($ormCompilerClass)) {
            $container->addCompilerPass(DoctrineOrmMapping::buildCompilerPass());
        }

        $container->addCompilerPass(new TwigPathCompilerPass());
        $container->addCompilerPass(new AdminMenuPass());
    }
}
