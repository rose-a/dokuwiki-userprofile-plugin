<?php
/**
 * DokuWiki Plugin userprofile (syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Alexander Rose <alex@rose-a.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
 
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_userprofile extends DokuWiki_Syntax_Plugin {
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
    * Get the type of syntax this plugin defines.
    *
    * @param none
    * @return String <tt>'substition'</tt> (i.e. 'substitution').
    * @public
    * @static
    */
    function getType(){
        return 'substition';
    }
    
    /**
    * Get the paragraph type of syntax this plugin defines.
    *
    * @param none
    * @return String <tt>'block'</tt>.
    * @public
    * @static
    */
    function getPType(){
        return 'block';
    }
	
	/**
    * Where to sort in?
    *
    * @param none
    * @return Integer <tt>6</tt>.
    * @public
    * @static
    */
    function getSort(){
        return 100;
    }
 
 
   /**
    * Connect lookup pattern to lexer.
    *
    * @param $aMode String The desired rendermode.
    * @return none
    * @public
    * @see render()
    */
    function connectTo($mode) {
		$this->Lexer->addSpecialPattern('\{\{userprofile>.*?\}\}',$mode,'plugin_userprofile');
    }

	/**
    * Handler to prepare matched data for the rendering process.
    *
    * @param $aMatch String The text matched by the patterns.
    * @param $aState Integer The lexer state for the match.
    * @param $aPos Integer The character position of the matched text.
    * @param $aHandler Object Reference to the Doku_Handler object.
    * @return Integer The current lexer state for the match.
    * @public
    * @see render()
    * @static
    */
    function handle($match, $state, $pos, &$handler){
		$match = substr($match, 15, -2); // strip {{userprofile> from start and }} from end
		$parsed = parse_url($match);
		$type = $parsed['path'];
		
		switch($type) {
			case 'table':
			default:
				return $this->_handleTable($parsed);
		} 
	}
	
	/**
    * Prepares a table output.
    *
    * @param array $parsedMatch The parsed pattern string.
    * @return array The current lexer state for the match.
    */
	function _handleTable($parsedMatch) {
        // get sqlite handle
        $sqlite = $this->hlp->_getDB();
        if(!$sqlite) return;
        
		// further parsing of the query string
		parse_str($parsedMatch['query'], $filters);
		$fragment = $parsedMatch['fragment'];
		
        // Get the users for which a row should be displayed
		if(count($filters)>0) {
            $cnt = 0;
            $sql = "SELECT val.[uid] FROM fieldvals val JOIN fields f ON f.[fid] = val.[fid] WHERE";
			foreach($filters as $field => $value){
                if($cnt>0) $sql .= " OR";
                $sql .= " (f.[name] = '".hsc($field)."' AND val.[value] = ?)";
                $params[] = $value;
                $cnt++;
            }
			$sql .= " GROUP BY [uid]";
            
            $res = $sqlite->query($sql, $params);
            $uid_array = $sqlite->res2arr($res);
            foreach($uid_array as $current){
                $uids[] = $current['uid'];
            }            
            $res = $sqlite->query("SELECT [user] FROM [users] WHERE [uid] IN (".implode(", ", $uids).") ORDER BY [name]");
		}
        else {
            $res = $sqlite->query("SELECT [user] FROM [users] ORDER BY [name]");
        }
        $users = $sqlite->res2arr($res);
        
        // get profiles
		foreach ($users as $current){
            $profiles[] = $this->hlp->getProfile($current);
        }
        
        // get field headers
        $res = $sqlite->query("SELECT [title] FROM [fields]");
        $headers = $sqlite->res2arr($res);     
        array_unshift($headers, array('title' => $this->getLang('realname')), array('title' => $this->getLang('email')));
        
        return array('type' => 'table', 'input' => $parsedMatch, 'rows' => $profiles, 'headers' => array_values($headers));
	}
    
    
    /**
    * Handle the actual output creation.
    *
    * @param $aFormat String The output format to generate.
    * @param $aRenderer Object A reference to the renderer object.
    * @param $aData Array The data created by the <tt>handle()</tt>
    * method.
    * @return Boolean <tt>TRUE</tt> if rendered successfully, or
    * <tt>FALSE</tt> otherwise.
    * @public
    * @see handle()
    */
    function render($mode, &$renderer, $data) {
        if($mode == 'xhtml'){
            switch($data['type']) {
                case 'table':
                    // render table header
                    $cnt_row = 0;                    
                    $renderer->doc .= '<table class="inline userprofile">'.PHP_EOL.
                                      '<thead><tr class="row'.$cnt_row.'">'.PHP_EOL;                                      
                    $cnt_col = 0;
                    foreach($data['headers'] as $header) {
                        $renderer->doc .= '<th class="row'.$cnt_col.'">'.hsc($header['title']).'</th>'.PHP_EOL;
                        $cnt_col++;
                    }
                    $renderer->doc .= '</thead></tr>'.PHP_EOL;
                    $cnt_row++;
                    // render content
                    if(!empty($data['rows'])){
                        foreach($data['rows'] as $row){
                            $renderer->doc .= '<tr class="row'.$cnt_row.'">'.PHP_EOL;
                            $cnt_col = 0;
                            foreach($row as $col) {
                                $renderer->doc .= '<td class="row'.$cnt_col.'">'.hsc($col).'</td>'.PHP_EOL;
                                $cnt_col++;
                            }
                            $renderer->doc .= '</tr>'.PHP_EOL;
                            $cnt_row++;
                        }
                    }
                    else {
                        // If no content is in rows, render "Nothing found" placeholder
                        $renderer->doc .= '<tr><td colspan="'.count($data['headers']).'"><center>Nothing found</center></tr>'.PHP_EOL;
                    }
                    
                    // close table and return
                    $renderer->doc .= '</table>'.PHP_EOL.PHP_EOL;
                    return true;
            }
            
        }
        return false;
    }

}
