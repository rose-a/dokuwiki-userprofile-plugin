<?php
/**
 * DokuWiki Plugin userprofile (Admin Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Alexander Rose <alex@rose-a.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

/**
 * Administration form for editing the additional user information
 */
class admin_plugin_userprofile_users extends DokuWiki_Admin_Plugin {
    /**
     * will hold the userprofile helper plugin
     * @var helper_plugin_userprofile
     */
    protected $hlp = null;
    protected $_auth = null;        // auth object

   /**
     * Constructor. Load helper plugin
     */
    public function __construct(){
        // Copied from usermanager plugin
        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;

        $this->setupLocale();

        if (!isset($auth)) {
            $this->_disabled = $this->lang['noauth'];
        } else if (!$auth->canDo('getUsers')) {
            $this->_disabled = $this->lang['nosupport'];
        } else {
            // we're good to go
            $this->_auth = & $auth;
        }
        
        // Get helper
        $this->hlp = plugin_load('helper', 'userprofile');
    }

    /**
     * Determine position in list in admin window
     * Lower values are sorted up
     *
     * @return int
     */
    public function getMenuSort() {
        return 400;
    }

    public function getMenuText() {
        return $this->getLang('menu_users');
    }

    /**
     * Return true for access only by admins (config:superuser) or false if managers are allowed as well
     *
     * @return bool
     */
    public function forAdminOnly() {
        return true;
    }

    /**
     * Return the text that is displayed at the main admin menu
     *
     * @param string $language lang code
     * @return string menu string
     */
    // public function getMenuText($language) {
    //     return $this->getLang('menu_alias');
    // }


    /**
     * Carry out required processing
     */
    public function handle() {
        if(!is_array($_REQUEST['up']) || !checkSecurityToken()) return;
        
        $userdata = $_REQUEST['up']['user'];
        
        // First save the user so it can be created if it doesn't already exist (bullshit, user has to be existing)
        //if(!$this->hlp->saveUser($userdata['user'], $userdata['name'], $userdata['email'])) return;
        
        // Then save the profile fields
        foreach($_REQUEST['up']['data'] as $field => $value){
            $this->hlp->saveField($userdata['user'], $field, $value);
        }
    }
    
    /**
     * Output html of the admin page
     */
    public function html() {
        global $ID;
        global $INPUT;
        
        if(is_null($this->_auth)) {
            print $this->lang['badauth'];
            return false;
        }
        
        $sqlite = $this->hlp->_getDB();
        if(!$sqlite) return;
        
        $fn = $INPUT->param('fn');
        if (is_array($fn)) {
            $cmd = key($fn);
            $param = is_array($fn[$cmd]) ? key($fn[$cmd]) : null;
        } else {
            $cmd = $fn;
            $param = null;
        }
        
        $user_list = $this->_auth->retrieveUsers($this->_start, $this->_pagesize, $this->_filter);

        
        echo $this->locale_xhtml('admin_intro');
        
        $form = new Doku_Form(array('method'=>'post'));
        $form->addHidden('page','userprofile_users');
        
        // List registered users
        $form->addElement(
            '<table>'.
            '<tr>'.
            '<th>'.$this->getLang('username').'</th>'.
            '<th>'.$this->getLang('realname').'</th>'.
            '<th>'.$this->getLang('email').'</th>'.
            '</tr>'
        );
        
        foreach ($user_list as $user => $userinfo) {
            extract($userinfo);
                /**
                 * @var string $name
                 * @var string $pass
                 * @var string $mail
                 * @var array  $grps
                 */
            if(!in_array('noprofile', $grps)) {
                $form->addElement(
                    '<tr>'.
                    '<td><a href="'.wl($ID,array('fn[edit]['.$user.']' => 1,
                                                                'do' => 'admin',
                                                                'page' => 'userprofile_users',
                                                                'sectok' => getSecurityToken())).
                                '" title="'.$this->lang['edit_prompt'].'">'.hsc($user).'</a></td>'.
                    '<td>'.hsc($name).'</td>'.
                    '<td>'.hsc($mail).'</td>'.
                    '</tr>'
                );
            }
        }
        $form->addElement('</table>');
        
        // Edit table
        if($cmd == "edit") {
            
            $user = $param;
            
            $profile = $this->hlp->getProfile($user);
            
            // create hidden fields
            $form->addHidden('up[user][user]',$user);
            $form->addHidden('up[user][name]',$user_list[$user]['name']);
            $form->addHidden('up[user][email]',$user_list[$user]['mail']);
                        
            $sql = "SELECT * FROM fields";
            $res = $sqlite->query($sql);
            $fields = $sqlite->res2arr($res);
            
            $form->addElement(
                '<table>'.
                '<tr>'.
                '<th colspan="2">'.$this->getLang('th_edit').'</th>'.
                '</tr>'.
                '<tr>'.
                '<td>'.$this->getLang('realname').'</td>'.
                '<td>'.hsc($user_list[$user]['name']).'</td>'.
                '</tr>'.
                '<tr>'.
                '<td>'.$this->getLang('email').'</td>'.
                '<td>'.hsc($user_list[$user]['mail']).'</td>'.
                '</tr>'
            );
            foreach($fields as $field){
                $form->addElement('<tr>');
    
                $form->addElement('<td>'.hsc($field['title']).'</td>');
                $form->addElement('<td>');
                
                $defaults_array = explode('|', $field['defaultval']);
                if(count($defaults_array) > 1) {
                    // create select field
                    $defaults_array = array_map('trim',$defaults_array);
                    $form->addElement(form_makeMenuField(
                                    'up[data]['.$field['name'].']',
                                    $defaults_array,
                                    $profile[$field['name']],''
                    ));
                }
                else {
                    // create regular text field
                    $form->addElement(form_makeTextField('up[data]['.$field['name'].']',$profile[$field['name']],''));
                }
                            
                $form->addElement('</td>');
                
                $form->addElement('</tr>');
            }
            
            $form->addElement(
                '<tr>'.
                '<td colspan="2">'               
            );
            $form->addElement(form_makeButton('submit','admin',$this->getLang('submit')));        
            $form->addElement('</td>');
            $form->addElement('</table>');
        }
        
        $form->printForm();
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
