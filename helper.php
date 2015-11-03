<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * This is the base class for all syntax classes, providing some general stuff
 */
class helper_plugin_userprofile extends DokuWiki_Plugin {
	/**
     * @var helper_plugin_sqlite initialized via _getDb()
     */
    protected $db = null;
	
	/**
     * load the sqlite helper
     *
     * @return helper_plugin_sqlite|false plugin or false if failed
     */
    function _getDB() {
        if($this->db === null) {
            $this->db = plugin_load('helper', 'sqlite');
            if($this->db === null) {
                msg('The userprofile plugin needs the sqlite plugin', -1);
                return false;
            }
            if(!$this->db->init('userprofile', dirname(__FILE__) . '/db/')) {
                $this->db = null;
                return false;
            }
        }
        return $this->db;
    }
	
}