<?php

namespace Ekyna\Bundle\CharacteristicsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Class AdminMenuPass
 * @package Ekyna\Bundle\CharacteristicsBundle\DependencyInjection\Compiler
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class AdminMenuPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ekyna_admin.menu.pool')) {
            return;
        }

        $pool = $container->getDefinition('ekyna_admin.menu.pool');

        $pool->addMethodCall('createGroup', array(array(
            'name'     => 'setting',
            'label'    => 'ekyna_setting.label',
            'icon'     => 'cogs',
            'position' => 100,
        )));
        $pool->addMethodCall('createEntry', array('setting', array(
            'name'     => 'characteristics',
            'route'    => 'ekyna_characteristics_choice_admin_home',
            'label'    => 'ekyna_characteristics.choice.label.plural',
            'resource' => 'ekyna_characteristics_choice',
            'position' => 90,
        )));
    }
}
