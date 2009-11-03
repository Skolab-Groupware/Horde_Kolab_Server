<?php
/**
 * Test the log decorator server factory.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the log decorator server factory.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Class_Server_Factory_Decorator_LogTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->factory = $this->getMock(
            'Horde_Kolab_Server_Factory_Interface'
        );
    }

    public function testMethodGetserverHasResultLoggedServerIfALoggerWasProvidedInTheConfiguration()
    {
        $this->factory->expects($this->once())
            ->method('getServer')
            ->will(
                $this->returnValue(
                    $this->getMock(
                        'Horde_Kolab_Server_Interface'
                    )
                )
            );
        $factory = new Horde_Kolab_Server_Factory_Decorator_Log(
            $this->factory, 'logger'
        );
        $this->assertType('Horde_Kolab_Server_Decorator_Log', $factory->getServer());
    }

    public function testMethodConstructHasParametersFactoryAndMixedLoggerParameter()
    {
        $factory = new Horde_Kolab_Server_Factory_Decorator_Log(
            $this->factory, 'logger'
        );
    }

    public function testMethodGetconnectionfactoryGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getConnectionFactory');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Log(
            $this->factory, 'logger'
        );
        $factory->getConnectionFactory();
    }

    public function testMethodGetserverGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getServer')
            ->will(
                $this->returnValue(
                    $this->getMock(
                        'Horde_Kolab_Server_Interface'
                    )
                )
            );
        $factory = new Horde_Kolab_Server_Factory_Decorator_Log(
            $this->factory, 'logger'
        );
        $factory->getServer();
    }

    public function testMethodGetconfigurationGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getConfiguration');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Log(
            $this->factory, 'logger'
        );
        $factory->getConfiguration();
    }

    public function testMethodGetconnectionGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getConnection');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Log(
            $this->factory, 'logger'
        );
        $factory->getConnection();
    }

    public function testMethodGetcompositeGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getComposite');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Log(
            $this->factory, 'logger'
        );
        $factory->getComposite();
    }

    public function testMethodGetobjectsGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getObjects');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Log(
            $this->factory, 'logger'
        );
        $factory->getObjects();
    }

    public function testMethodGetstructureGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getStructure');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Log(
            $this->factory, 'logger'
        );
        $factory->getStructure();
    }

    public function testMethodGetsearchGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getSearch');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Log(
            $this->factory, 'logger'
        );
        $factory->getSearch();
    }

    public function testMethodGetschemaGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getSchema');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Log(
            $this->factory, 'logger'
        );
        $factory->getSchema();
    }
}