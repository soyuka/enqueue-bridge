<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sam\Symfony\Bridge\EnqueueMessage\Tests;

use PHPUnit\Framework\TestCase;
use Sam\Symfony\Bridge\EnqueueMessage\Bundle\DependencyInjection\EnqueueBridgeExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EnqueueBridgeExtensionTest extends TestCase
{
    private $extension;

    public function setUp()
    {
        $this->extension = new EnqueueBridgeExtension();
    }

    public function testConstruct()
    {
        $this->extension = new EnqueueBridgeExtension();
        $this->assertInstanceOf(ExtensionInterface::class, $this->extension);
        $this->assertInstanceOf(ConfigurationExtensionInterface::class, $this->extension);
    }

    public function testLoad()
    {
        $config = array('enqueue_bridge' => array('queue' => 'message'));
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('kernel.debug')->shouldBeCalled();
        $containerBuilderProphecy->setDefinitions(Argument::that(function ($data) {
            $this->assertSame(array_keys($data), array('enqueue_bridge.receiver', 'enqueue_bridge.sender'));

            return true;
        }))->shouldBeCalled();

        $this->extension->load($config, $containerBuilderProphecy->reveal());
    }
}
