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

   /**
     * Constructor. Load helper plugin
     */
    public function __construct(){
        
        $this->hlp = plugin_load('helper', 'userprofile');
    }

    /**
     * Determine position in list in admin window
     * Lower values are sorted up
     *
     * @return int
     */
    public function getMenuSort() {
        return 500;
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
        // :TODO: add functionality
    }
    
    /**
     * Output html of the admin page
     */
    public function html() {
        $sqlite = $this->hlp->_getDB();
        if(!$sqlite) return;

        echo $this->locale_xhtml('admin_intro');

        // Nothing happens here yet 
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
