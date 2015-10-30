<?php
/**
 * DokuWiki Plugin userprofile (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Alexander Rose <alex@rose-a.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_userprofile_userModified extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {

       $controller->register_hook('AUTH_USER_CHANGE', 'AFTER', $this, 'handle_auth_user_change');
   
    }

    /**
     * [Custom event handler which performs action]
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */

    public function handle_auth_user_change(Doku_Event &$event, $param) {
        
        // Call appropriate function        
        switch ($event->data['type']) {
            case 'create':
                $this->_createProfile($event->data['params']);
                break;
            case 'modify':
                $this->_modifyProfile($event->data['params'], $event->data['modification_result']);
                break;
            case 'delete':
                $this->_deleteProfile($event->data['params']);
                break;    
        }
    }
    
    /**
     * Creates a new userprofile page
     * 
     * @param array     $params the data['params'] component of the AUTH_USER_CHANGE event
     *
     * @return void
     */
    private function _createProfile($params) {
        // get config vars
        $ns = getConf('namespace');
        //$template = getConf('template');
        
        // extract event params
        $user = $params[0];
        $fields = array('name'     => $params[2],
                         'email'    => $params[3]);
        
        // compose id for new user profile page        
        $id = $user;
        resolve_pageid($ns,$id,$exists);
        
        // open dataentry block
        $text = "---- dataentry userprofile ----".PHP_EOL;
        
        // add a row for each field member
        foreach($fields as $key => $value){
            // add key : value line to text
            $text.= _dataFieldEscapeMulti($key)." : ".$value.PHP_EOL;
        }
        // close dataentry block
        $text.= "----";
        
        // create userprofile page
        saveWikiText($id, $text, "created by userprofile plugin"); 
    }
    
    /**
     * Deletes a userprofile page
     * 
     * @param array     $params the data['params'] component of the AUTH_USER_CHANGE event
     *
     * @return void
     */
    private function _deleteProfile($params) {
        // get config vars
        $ns = getConf('namespace');
        //$template = getConf('template');
        
        // extract event params
        $user = $params[0];
        $id = $user;
        
        // check if page exists
        resolve_pageid($ns,$id,$exists);
        
        // if page exists, delete it by writing an empty string to $text
        if($exists)
            saveWikiText($id, "", "deleted by userprofile plugin (user deleted)"); 
    }
    
    /**
     * Modifies a userprofile page
     * 
     * @param array $params                 the data['params'] component of the AUTH_USER_CHANGE event
     * @param bool $modification_result     the modification was accepted
     *
     * @return void
     */
    private function _modifyProfile($params, $modification_result) {
        // get config vars
        $ns = getConf('namespace');
        //$template = getConf('template');
        
        // extract event params
        $user = $params[0];
        $id = $user;
        $changed = $params[1];
        
        // check if page exists
        resolve_pageid($ns,$id,$exists);
        
        // if page does not exist, build a "create" param-array and call _createProfile()
        if(!$exists && $userdata = getUserData($user, false) ){
            $createparams[0] = $user;
            $createparams[2] = $userdata['name'];
            $createparams[3] = $userdata['mail'];
            $this->_createProfile($createparams);
            return;
        } 
        
        // else check modification_result
        if(!$modification_result) return;
        
        // get current raw text from page
        $text = rawWiki($id);
        foreach($changed as $key => $value){
            $escapedkey = _dataFieldEscapeMulti($key);
            // replace value
            $text = preg_replace('/^'.$escapedkey.'([:\s]+)(.*)$/m', $escapedkey.'$1'.$value, $text);
        }
        
        // check if the username was changed
        if(!empty($changed['user']) && $user != $changed['user']){
            // resolve new user id
            $newid = $changed['user'];
            resolve_pageid($ns,$newid,$exists);
            
            // create new userprofile page
            saveWikiText($newid, $text, "created by userprofile plugin"); 
            
            // delete old page
            saveWikiText($id, "", "deleted by userprofile plugin (username changed to '".$changed['user']."')"); 
            
            // finish
            return;
        }
        
        // write the changes to the page
        saveWikiText($id, $text, "modified by userprofile plugin"); 
        
    }
    
     /**
     * Appends an underscore to the key if the key's last character is a 's'
     * 
     * @param string $key   the datafield name
     *
     * @return string       the escaped key
     */
    private function _dataFieldEscapeMulti($key){
        // if the last char of key is an 's' append an underscore
        if(substr($key, -1) == 's') return $key .= "_";
        
        // else leave it as is
        return $key;
    }
}

// vim:ts=4:sw=4:et:
