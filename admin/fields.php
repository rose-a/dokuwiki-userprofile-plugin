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
 * Administration form for configuring the additional profile fields
 */
class admin_plugin_userprofile_fields extends DokuWiki_Admin_Plugin {
    /**
     * will hold the userprofile helper plugin
     * @var helper_plugin_userprofile
     */
    protected $hlp = null;

   /**
     * Constructor. Load helper plugin
     */
    public function __construct(){
        $this->db = plugin_load('helper', 'userprofile');
    }

    /**
     * Determine position in list in admin window
     * Lower values are sorted up
     *
     * @return int
     */
    public function getMenuSort() {
        return 501;
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
    public function getMenuText($language) {
        return $this->getLang('menu_alias');
    }


    /**
     * Carry out required processing
     */
    public function handle() {
        if(!is_array($_REQUEST['d']) || !checkSecurityToken()) return;

        $sqlite = $this->hlp->_getDB();
        if(!$sqlite) return;

        $sqlite->query("BEGIN TRANSACTION");
        if (!$sqlite->query("DELETE FROM aliases")) {
            $sqlite->query('ROLLBACK TRANSACTION');
            return;
        }
        foreach($_REQUEST['d'] as $row){
            $row = array_map('trim',$row);
            $row['name'] = utf8_strtolower($row['name']);
            $row['name'] = rtrim($row['name'],'s');
            if(!$row['name']) continue;

            // Clean default
            $arr = preg_split('/\s*,\s*/', $row['default']);
            $arr = array_unique($arr);
            $row['default'] = implode(', ', $arr);

            if (!$sqlite->query("INSERT INTO aliases (name, title, default)
                                 VALUES (?,?,?)",$row)) {
                $sqlite->query('ROLLBACK TRANSACTION');
                return;
            }
        }
        $sqlite->query("COMMIT TRANSACTION");
    }
    
    /**
     * Output html of the admin page
     */
    public function html() {
        $sqlite = $this->hlp->_getDB();
        if(!$sqlite) return;

        //echo $this->locale_xhtml('admin_intro');

        $sql = "SELECT * FROM aliases ORDER BY name";
        $res = $sqlite->query($sql);
        $rows = $sqlite->res2arr($res);

        $form = new Doku_Form(array('method'=>'post'));
        $form->addHidden('page','data_aliases');
        $form->addElement(
            '<table class="inline">'.
            '<tr>'.
            '<th>'.$this->getLang('name').'</th>'.
            '<th>'.$this->getLang('title').'</th>'.
            '<th>'.$this->getLang('default').'</th>'.
            '</tr>'
        );

        // add empty row for adding a new entry
        $rows[] = array('name'=>'','title'=>'','default'=>'');

        $cur = 0;
        foreach($rows as $row){
            $form->addElement('<tr>');

            $form->addElement('<td>');
            $form->addElement(form_makeTextField('d['.$cur.'][name]',$row['name'],''));
            $form->addElement('</td>');
            
            $form->addElement('<td>');
            $form->addElement(form_makeTextField('d['.$cur.'][title]',$row['title'],''));
            $form->addElement('</td>');

            $form->addElement('<td>');
            $form->addElement(form_makeTextField('d['.$cur.'][default]',$row['default'],''));
            $form->addElement('</td>');
            
            $form->addElement('</tr>');

            $cur++;
        }

        $form->addElement('</table>');
        $form->addElement(form_makeButton('submit','admin',$this->getLang('submit')));
        $form->printForm();
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
