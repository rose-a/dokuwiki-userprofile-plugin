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
     * will hold the userprofile helper plugin
     * @var helper_plugin_userprofile
     */
    protected $hlp = null;

   /**
     * Constructor. Load helper plugin
     */
    public function __construct(){
        $this->hlp = plugin_load('helper', 'userprofile');
    }

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
        // return if user belongs to group 'noprofile' 
        if(in_array('noprofile', $params[4])) return;
        
        // get config vars
        $ns = $this->getConf('namespace');
        //$template = $this->getConf('template');
        
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
            $text.= $this->hlp->_dataFieldEscapeMulti($key)." : ".$value.PHP_EOL;
        }
        // close dataentry block
        $text.= "----";
        
        // create userprofile page
        saveWikiText($id, $text, "userprofile plugin: profile page created"); 
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
        $ns = $this->getConf('namespace');
        //$template = $this->getConf('template');
        
        // extract event params
        $user = $params[0][0];
        $id = $user;
        
        // check if page exists
        resolve_pageid($ns,$id,$exists);
        
        // if page exists, delete it by writing an empty string to $text
        if($exists)
            saveWikiText($id, "", "userprofile plugin: deleted (user deleted)"); 
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
        global $auth;
        // get config vars
        $ns = $this->getConf('namespace');
        //$template = $this->getConf('template');
        
        // extract event params
        $user = $params[0];
        $id = $user;
        $changed = $params[1];
        
        // check if page exists
        resolve_pageid($ns,$id,$exists);
        
        // get userdata
        $userdata = $auth->getUserData($user, false);
        print_r($this->_auth);
        $noprofile = in_array('noprofile', $userdata['grps']);
        
        // if page does not exist, build a "create" param-array and call _createProfile()
        if(!$exists){
            if(!$noprofile){
                $createparams[0] = $user;
                $createparams[2] = $userdata['name'];
                $createparams[3] = $userdata['mail'];
                $this->_createProfile($createparams);
            }
            return;
        } 
        
        // else check modification_result
        if(!$modification_result) return;
        
        // check noprofile group
        if($noprofile){
            saveWikiText($id, "", "userprofile plugin: deleted (user added to 'noprofile' group)"); 
            return;
        }
        
        // get current raw text from page
        $text = rawWiki($id);
        foreach($changed as $key => $value){
            $escapedkey = $this->hlp->_dataFieldEscapeMulti($key);
            // replace value
            $text = preg_replace('/^'.$escapedkey.'([:\t ]+)(.*)$/m', $escapedkey.'$1'.$value, $text);
        }
        
        // check if the username was changed
        if(!empty($changed['user']) && $user != $changed['user']){
            // resolve new user id
            $newid = $changed['user'];
            resolve_pageid($ns,$newid,$exists);
            
            // create new userprofile page
            saveWikiText($newid, $text, "userprofile plugin: profile page created"); 
            
            // delete old page
            saveWikiText($id, "", "userprofile plugin: deleted (username changed to '".$changed['user']."')"); 
            
            // finish
            return;
        }
        
        // write the changes to the page
        saveWikiText($id, $text, "userprofile plugin: user modified"); 
        
    }
}

// vim:ts=4:sw=4:et:
