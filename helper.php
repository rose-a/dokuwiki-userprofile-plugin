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
     * Saves a custom profile fields value to the database
     * 
     * @param string $user the concerned username
     * @param string $field the name of the custom profile field
     * @param string $value the value of the field
     * @return bool 
     */
    function saveField($user, $field, $value){        
        $sqlite = $this->_getDB();
        if(!$sqlite) return false;
        
        // check if field is a legit field
        $res = $sqlite->query("SELECT [fid] FROM fields WHERE [name] = ?", $field);
        $fid = $sqlite->res2row($res)['fid'];
        // If no field with the given name is found, return
        if(!$fid) return false;
        
        // check if user exists in db
        $res = $sqlite->query("SELECT [uid] FROM users WHERE [user] = ?", $user);
        $uid = $sqlite->res2row($res)['uid'];
        
        // If no user with the given name is found, return
        if(!$uid) return false;
        
        // get id of the field entry for the current user
        $res = $sqlite->query("SELECT [vid] FROM fieldvals WHERE [fid] = ? AND [uid] = ?", array($fid, $uid));
        $vid = $sqlite->res2row($res)['vid'];
        
        $sqlite->query("BEGIN TRANSACTION");
        if($vid)
            $res = $sqlite->query("UPDATE fieldvals SET [value] = ? WHERE [vid] = ?", array($value, $vid));
        else
            $res = $sqlite->query("INSERT INTO fieldvals ([fid], [uid], [value]) VALUES (?,?,?)", array($fid, $uid, $value));
            
        if(!$res){
            $sqlite->query("ROLLBACK TRANSACTION");
            return false;            
        } 
                
        $sqlite->query("COMMIT TRANSACTION");
        return true;
    }
    
    /**
     * Saves a user to the database
     *
     * @param string $user the username
     * @param string $name the users real name
     * @param string $email the users email
     * @param string [$olduser] the users old username
     * @return int|false the uid if the user was successfully saved, otherwise false 
     */
    function saveUser($user, $name, $email, $olduser = ""){
        $sqlite = $this->_getDB();
        if(!$sqlite) return false;
        
        if(empty($olduser)) $olduser = $user;
        
        // check if user exists in db
        $res = $sqlite->query("SELECT [uid] FROM users WHERE [user] = ?", $olduser);
        $uid = $sqlite->res2row($res)['uid'];
        
        $sqlite->query("BEGIN TRANSACTION");
        if($uid)
            $res = $sqlite->query("UPDATE users SET [user] = ?, [name] = ?, [email] = ? WHERE [uid] = ?", array($user, $name, $email, $uid));
        else
            $res = $sqlite->query("INSERT INTO users ([user], [name], [email]) VALUES (?,?,?)", array($user, $name, $email));
        
        if(!$res){
            $sqlite->query("ROLLBACK TRANSACTION");
            return false;            
        } 
                
        $sqlite->query("COMMIT TRANSACTION");
        return true;
    }
    
    /**
     * Removes a user from the database
     *
     * @param string $user the username
     * @return bool 
     */
    function deleteUser($user){
        $sqlite = $this->_getDB();
        if(!$sqlite) return false;
        
        $sqlite->query("BEGIN TRANSACTION");
        // delete all fieldvals for the user
        if(!$sqlite->query("DELETE FROM fieldvals WHERE [uid] = (SELECT [uid] FROM users WHERE [user] = ?)", $user)){
            $sqlite->query("ROLLBACK TRANSACTION");
            return false;
        } 
        
        // delete the user
        if(!$sqlite->query("DELETE FROM users WHERE [user] = ?", $user)){
            $sqlite->query("ROLLBACK TRANSACTION");
            return false;
        }
                
        $sqlite->query("COMMIT TRANSACTION");
        return true;
    }
    
    /**
     * Retrieves a users profile from the database
     *
     * @param string $user the username
     * @return array|false the users profile if found in db 
     */
    function getProfile($user){
        $sqlite = $this->_getDB();
        if(!$sqlite) return false;
        
        $res = $sqlite->query("SELECT [uid], [name], [email]  FROM users WHERE [user] = ?", $user);
        $userdata = $sqlite->res2row($res);
        
        if(!$userdata['uid']) return false;
        $uid = $userdata['uid'];
        unset($userdata['uid']);
        
        $sql = "SELECT f.[name] as field, val.value as value FROM fields f ".
               "JOIN fieldvals val ON f.[fid] = val.[fid] ".
               "WHERE val.[uid] = ?";
        $res = $sqlite->query($sql, $uid);
        $fields = $sqlite->res2arr($res);
        
        foreach($fields as $current){
            $userdata[$current['field']] = $current['value'];
        }       
        
        return $userdata;       
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