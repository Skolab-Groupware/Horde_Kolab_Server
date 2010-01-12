<?php
/**
 * Provides functions required by several Kolab_Server tests.
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
require_once dirname(__FILE__) . '/Autoload.php';

require_once dirname(__FILE__) . '/Constraints/Restrictkolabusers.php';
require_once dirname(__FILE__) . '/Constraints/Restrictgroups.php';
require_once dirname(__FILE__) . '/Constraints/Searchuid.php';

/**
 * Skip LDAP based tests if we don't have ldap or Net_LDAP2.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_TestCase extends PHPUnit_Framework_TestCase
{
    protected function getComposite()
    {
        return $this->getMock(
            'Horde_Kolab_Server_Composite_Interface'
        );
    }

    protected function getMockedComposite()
    {
        return new Horde_Kolab_Server_Composite_Base(
            $this->getMock('Horde_Kolab_Server_Interface'),
            $this->getMock('Horde_Kolab_Server_Objects_Interface'),
            $this->getMock('Horde_Kolab_Server_Structure_Interface'),
            $this->getMock('Horde_Kolab_Server_Search_Interface'),
            $this->getMock('Horde_Kolab_Server_Schema_Interface')
        );
    }

    public function isRestrictedToGroups()
    {
        return new Horde_Kolab_Server_Constraint_Restrictgroups();
    }

    public function isRestrictedToKolabUsers()
    {
        return new Horde_Kolab_Server_Constraint_Restrictedkolabusers();
    }

    public function isSearchingByUid()
    {
        return new Horde_Kolab_Server_Constraint_Searchuid();
    }
}