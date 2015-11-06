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
        
        // save user        
        $this->hlp->saveUser($params[0], $params[2], $params[3]);
    }
    
    /**
     * Deletes a userprofile page
     * 
     * @param array     $params the data['params'] component of the AUTH_USER_CHANGE event
     *
     * @return void
     */
    private function _deleteProfile($params) {        
        // extract event params
        $user = $params[0][0];
        
        // delete user
        $this->hlp->deleteUser($user);
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
                
        // extract event params
        $user = $params[0];
        $changed = $params[1];
        $olduser = null;
        
                // check if the username was changed
        if(!empty($changed['user']) && $user != $changed['user']){
            $olduser = $user;
            $user = $changed['user'];
        }
        
        // get userdata
        $userdata = $auth->getUserData($user, false);
        $noprofile = in_array('noprofile', $userdata['grps']);        
        
        // check noprofile group
        if($noprofile){
            // delete user
            $this->hlp->deleteUser($user);
            return;
        }
        
        // save user 
        $uid = $this->hlp->saveUser($user, $userdata['name'], $userdata['mail'], $olduser);
    }
}

// vim:ts=4:sw=4:et:
