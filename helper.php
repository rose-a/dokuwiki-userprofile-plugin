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
     * get the user defined fields
     *
     * @return array|false custom profile fields or false if failed
     */
    function getFields() {
        $sqlite = $this->_getDB();
        if(!$sqlite) return false;
        
        $sql = "SELECT * FROM fields";
        $res = $sqlite->query($sql);
        $rows = $sqlite->res2arr($res);
        
        return $rows;
    }
    
    /**
     * Creates a dataentry for the given array
     *
     * @param array $fields An array containing the keys and corresponding values for the dataentry
     * @return string|false the dataentry string or false
     */
    function createDataentry($fields) {
        // open dataentry block
        $text = "---- dataentry userprofile ----".PHP_EOL;
        
        // add a row for each field member
        foreach($fields as $key => $value){
            // add key : value line to text
            $text.= $this->_dataFieldEscapeMulti($key)." : ".$value.PHP_EOL;
        }
        // close dataentry block
        $text.= "----";
        
        return $text;
    }
	
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
    
    /**
     * Appends an underscore to the key if the key's last character is a 's'
     * 
     * @param string $key   the datafield name
     *
     * @return string       the escaped key
     */
    function _dataFieldEscapeMulti($key){
        // if the last char of key is an 's' append an underscore
        if(substr($key, -1) == 's') return $key .= "_";
        
        // else leave it as is
        return $key;
    }
	
}