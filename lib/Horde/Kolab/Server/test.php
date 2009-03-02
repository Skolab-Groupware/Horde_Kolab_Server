<?php
/**
 * A driver for simulating a Kolab user database stored in LDAP.
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
 * This class provides a class for testing the Kolab Server DB.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_test extends Horde_Kolab_Server_ldap
{

    /**
     * Array holding the current result set.
     *
     * @var array
     */
    private $_result;

    /**
     * Buffer for error numbers.
     *
     * @var int
     */
    private $_errno = 0;

    /**
     * Buffer for error descriptions.
     *
     * @var int
     */
    private $_error = '';

    /**
     * Attribute used for sorting.
     *
     * @var string
     */
    private $_sort_by;

    /**
     * A result cache for iterating over the result.
     *
     * @var array
     */
    private $_current_result;

    /**
     * An index into the current result for iterating.
     *
     * @var int
     */
    private $_current_index;

    /**
     * Construct a new Horde_Kolab_Server object.
     *
     * @param array $params Parameter array.
     */
    public function __construct($params = array())
    {
        if (isset($params['data'])) {
            $GLOBALS['KOLAB_SERVER_TEST_DATA'] = $params['data'];
        } else {
            if (!isset($GLOBALS['KOLAB_SERVER_TEST_DATA'])) {
                $GLOBALS['KOLAB_SERVER_TEST_DATA'] = array();
            }
        }
        parent::__construct($params);
    }

    /**
     * Binds the LDAP connection with a specific user and pass.
     *
     * @param string $dn DN to bind with
     * @param string $pw Password associated to this DN.
     *
     * @return boolean Whether or not the binding succeeded.
     *
     * @throws Horde_Kolab_Server_Exception If the user does not exit, he has no
     *                                      password, provided an incorrect
     *                                      password or anonymous binding is not
     *                                      allowed.
     */
    protected function bind($dn = false, $pw = '')
    {
        if (!$dn) {
            if (isset($this->params['uid'])) {
                $dn = $this->params['uid'];
            } else {
                $dn = '';
            }
        }
        if (!$pw) {
            if (isset($this->params['pass'])) {
                $pw = $this->params['pass'];
            }
        }

        if (!empty($dn)) {
            if (!isset($GLOBALS['KOLAB_SERVER_TEST_DATA'][$dn])) {
                throw new Horde_Kolab_Server_Exception('User does not exist!');
            }

            $this->_bound = true;

            try {
                $data = $this->read($dn, array('userPassword'));
            } catch (Horde_Kolab_Server_Exception $e) {
                $this->_bound = false;
                throw $e;
            }
            if (!isset($data['userPassword'])) {
                $this->_bound = false;
                throw new Horde_Kolab_Server_Exception('User has no password entry!');
            }
            $this->_bound = $data['userPassword'][0] == $pw;
            if (!$this->_bound) {
                throw new Horde_Kolab_Server_Exception('Incorrect password!');
            }
        } else if (!empty($this->params['no_anonymous_bind'])) {
            $this->_bound = false;
            throw new Horde_Kolab_Server_Exception('Anonymous bind is not allowed!');
        } else {
            $this->_bound = true;
        }
        return $this->_bound;
    }

    /**
     * Disconnect from LDAP.
     *
     * @return NULL
     */
    public function unbind()
    {
        $this->_bound = false;
    }

    /**
     * Parse LDAP filter.
     * Partially derived from Net_LDAP_Filter.
     *
     * @param string $filter The filter string.
     *
     * @return array An array of the parsed filter.
     *
     * @throws Horde_Kolab_Server_Exception If parsing the filter expression
     *                                      fails.
     */
    public function parse($filter)
    {
        $result = array();
        if (preg_match('/^\((.+?)\)$/', $filter, $matches)) {
            if (in_array(substr($matches[1], 0, 1), array('!', '|', '&'))) {
                $result['op']  = substr($matches[1], 0, 1);
                $result['sub'] = $this->parseSub(substr($matches[1], 1));
                return $result;
            } else {
                if (stristr($matches[1], ')(')) {
                    throw new Horde_Kolab_Server_Exception('Filter parsing error: invalid filter syntax - multiple leaf components detected!');
                } else {
                    $filter_parts = preg_split('/(?<!\\\\)(=|=~|>|<|>=|<=)/',
                                               $matches[1], 2,
                                               PREG_SPLIT_DELIM_CAPTURE);
                    if (count($filter_parts) != 3) {
                        throw new Horde_Kolab_Server_Exception('Filter parsing error: invalid filter syntax - unknown matching rule used');
                    } else {
                        $result['att'] = $filter_parts[0];
                        $result['log'] = $filter_parts[1];
                        $result['val'] = $filter_parts[2];
                        return $result;
                    }
                }
            }
        } else {
            throw new Horde_Kolab_Server_Exception(sprintf("Filter parsing error: %s - filter components must be enclosed in round brackets",
                                                           $filter));
        }
    }

    /**
     * Parse a LDAP subfilter.
     *
     * @param string $filter The subfilter string.
     *
     * @return array An array of the parsed subfilter.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function parseSub($filter)
    {
        $result  = array();
        $level   = 0;
        $collect = '';
        while (preg_match('/^(\(.+?\))(.*)/', $filter, $matches)) {
            if (in_array(substr($matches[1], 0, 2), array('(!', '(|', '(&'))) {
                $level++;
            }
            if ($level) {
                $collect .= $matches[1];
                if (substr($matches[2], 0, 1) == ')') {
                    $collect   .= ')';
                    $matches[2] = substr($matches[2], 1);
                    $level--;
                    if (!$level) {
                        $result[] = $this->parse($collect);
                    }
                }
            } else {
                $result[] = $this->parse($matches[1]);
            }
            $filter = $matches[2];
        }
        return $result;
    }

    /**
     * Search for object data.
     *
     * @param string $filter The LDAP search filter.
     * @param string $params Additional search parameters.
     * @param string $base   The search base
     *
     * @return array The result array.
     *
     * @throws Horde_Kolab_Server_Exception If the search operation encountered
     *                                      a problem.
     */
    public function search($filter = null, $params = array(), $base = null)
    {
        if (!$this->_bound) {
            $result = $this->bind();
        }

        $filter = $this->parse($filter);
        if (isset($params['attributes'])) {
            $attributes = $params['attributes'];
            if (!is_array($attributes)) {
                $attributes = array($attributes);
            }
        } else {
            $attributes = array();
        }
        $result = $this->doSearch($filter, $attributes);
        if (empty($result)) {
            return null;
        }
        if ($base) {
            $subtree = array();
            foreach ($result as $entry) {
                if (strpos($entry['dn'], $base)) {
                    $subtree[] = $entry;
                }
            }
            $result = $subtree;
        }
        return $this->getEntries($result);
    }

    /**
     * Perform the search.
     *
     * @param array $filter     Filter criteria-
     * @param array $attributes Restrict the search result to
     *                          these attributes.
     *
     * @return array A LDAP serach result.
     *
     * @throws Horde_Kolab_Server_Exception If the search operation is not
     *                                      available.
     */
    protected function doSearch($filter, $attributes = null)
    {
        if (isset($filter['log'])) {
            $result = array();
            foreach ($GLOBALS['KOLAB_SERVER_TEST_DATA'] as $element) {
                if (isset($element['data'][$filter['att']])) {
                    switch ($filter['log']) {
                    case '=':
                        $value = $element['data'][$filter['att']];
                        if (($filter['val'] == '*' && !empty($value))
                            || $value == $filter['val']
                            || (is_array($value)
                                && in_array($filter['val'], $value))) {
                            if (empty($attributes)) {
                                $result[] = $element;
                            } else {
                                $selection = $element;
                                foreach ($element['data'] as $attr => $value) {
                                    if (!in_array($attr, $attributes)) {
                                        unset($selection['data'][$attr]);
                                    }
                                }
                                $result[] = $selection;
                            }
                        }
                        break;
                    default:
                        throw new Horde_Kolab_Server_Exception(_("Not implemented!"));
                    }
                }
            }
            return $result;
        } else {
            $subresult   = array();
            $filtercount = count($filter['sub']);
            foreach ($filter['sub'] as $subfilter) {
                $subresult = array_merge($subresult,
                                         $this->doSearch($subfilter,
                                                         $attributes));
            }
            $result = array();
            $dns    = array();
            foreach ($subresult as $element) {
                $dns[] = $element['dn'];

                $result[$element['dn']] = $element;
            }
            switch ($filter['op']) {
            case '&':
                $count     = array_count_values($dns);
                $selection = array();
                foreach ($count as $dn => $value) {
                    if ($value == $filtercount) {
                        $selection[] = $result[$dn];
                    }
                }
                return $selection;
            case '|':
                return array_values($result);
            case '!':
                $dns = array();
                foreach ($result as $entry) {
                    if (!in_array($entry['dn'], $dns) ) {
                        $dns[] = $entry['dn'];
                    }
                }
                $all_dns = array_keys($GLOBALS['KOLAB_SERVER_TEST_DATA']);
                $diff    = array_diff($all_dns, $dns);

                $result = array();
                foreach ($diff as $dn) {
                    if (empty($attributes)) {
                        $result[] = $GLOBALS['KOLAB_SERVER_TEST_DATA'][$dn];
                    } else {
                        $selection = $GLOBALS['KOLAB_SERVER_TEST_DATA'][$dn];
                        foreach ($GLOBALS['KOLAB_SERVER_TEST_DATA'][$dn]['data']
                                 as $attr => $value) {
                            if (!in_array($attr, $attributes)) {
                                unset($selection['data'][$attr]);
                            }
                        }
                        $result[] = $selection;
                    }
                }
                return $result;
            default:
                throw new Horde_Kolab_Server_Exception(_("Not implemented!"));
            }
        }
    }

    /**
     * Read object data.
     *
     * @param string $dn    The object to retrieve.
     * @param string $attrs Restrict to these attributes
     *
     * @return array An array of attributes.
     *
     * @throws Horde_Kolab_Server_Exception If the object does not exist.
     */
    public function read($dn, $attrs = null)
    {
        if (!$this->_bound) {
            $result = $this->bind();
        }

        if (!isset($GLOBALS['KOLAB_SERVER_TEST_DATA'][$dn])) {
            throw new Horde_Kolab_Server_Exception(sprintf("LDAP Error: No such object: %s: No such object",
                                                           $dn));
        }
        if (empty($attrs)) {
            return $GLOBALS['KOLAB_SERVER_TEST_DATA'][$dn]['data'];
        } else {
            $result = array();
            $data   = $GLOBALS['KOLAB_SERVER_TEST_DATA'][$dn]['data'];

            foreach ($attrs as $attr) {
                if (isset($data[$attr])) {
                    $result[$attr] = $data[$attr];
                    array_push($result, $attr);
                }
            }
            return $result;
        }
    }

    /**
     * Add a new object
     *
     * @param string $dn   The DN of the object to be added.
     * @param array  $data The attributes of the object to be added.
     *
     * @return boolean  True if adding succeeded.
     */
    public function save($dn, $data)
    {
        if (!$this->_bound) {
            $result = $this->bind();
        }

        $ldap_data = array();
        foreach ($data as $key => $val) {
            if (!is_array($val)) {
                $val = array($val);
            }
            $ldap_data[$key] = $val;
        }

        $GLOBALS['KOLAB_SERVER_TEST_DATA'][$dn] = array(
            'dn' => $dn,
            'data' => $ldap_data
        );
    }

    /**
     * Return the attributes of an entry.
     *
     * @param array $entry The LDAP entry.
     *
     * @return array  The attributes of the entry.
     */
    protected function getAttributes($entry)
    {
        if (is_array($entry)) {
            return $entry;
        }
        return false;
    }

    /**
     * Return the current entry of a result.
     *
     * @return mixe  The current entry of the result or false.
     */
    protected function fetchEntry()
    {
        if (is_array($this->_current_result)
            && $this->_current_index < count($this->_current_result)) {

            $data = array_keys($this->_current_result[$this->_current_index]['data']);

            $data['dn'] = array($this->_current_result[$this->_current_index]['dn']);

            foreach ($this->_current_result[$this->_current_index]['data']
                     as $attr => $value) {
                if (!is_array($value)) {
                    $value = array($value);
                }
                $data[$attr]          = $value;
            }
            $this->_current_index++;
            return $data;
        }
        return false;
    }

    /**
     * Return the first entry of a result.
     *
     * @param array $result The LDAP search result.
     *
     * @return mixed The first entry of the result or false.
     */
    protected function firstEntry($result)
    {
        $this->_current_result = $result;
        $this->_current_index  = 0;
        return $this->fetchEntry();
    }

    /**
     * Return the next entry of a result.
     *
     * @param resource $entry The current LDAP entry.
     *
     * @return resource The next entry of the result.
     */
    protected function nextEntry($entry)
    {
        return $this->fetchEntry();
    }

    /**
     * Return the entries of a result.
     *
     * @param array $result The LDAP search result.
     *
     * @return mixed The entries of the result or false.
     */
    protected function getEntries($result)
    {
        if (is_array($result)) {
            $data          = array();
            foreach ($result as $entry) {
                $t       = $entry['data'];
                $t['dn'] = $entry['dn'];
                $data[]  = $t;
            }
            return $data;
        }
        return false;
    }

    /**
     * Sort the entries of a result.
     *
     * @param resource &$result   The LDAP search result.
     * @param string   $attribute The attribute used for sorting.
     *
     * @return boolean  True if sorting succeeded.
     */
    public function sort(&$result, $attribute)
    {
        if (empty($result)) {
            return $result;
        }

        $this->_sort_by = $attribute;
        usort($result, array($this, 'resultSort'));
        return false;
    }

    /**
     * Sort two entries.
     *
     * @param array $a First entry.
     * @param array $b Second entry.
     *
     * @return int  Comparison result.
     */
    protected function resultSort($a, $b)
    {
        $x = isset($a['data'][$this->_sort_by][0])?$a['data'][$this->_sort_by][0]:'';
        $y = isset($b['data'][$this->_sort_by][0])?$b['data'][$this->_sort_by][0]:'';
        return strcasecmp($x, $y);
    }


    /**
     * Return the current LDAP error number.
     *
     * @return int  The current LDAP error number.
     */
    protected function errno()
    {
        return $this->_errno;
    }

    /**
     * Return the current LDAP error description.
     *
     * @return string  The current LDAP error description.
     */
    protected function error()
    {
        return $this->_error;
    }

}
