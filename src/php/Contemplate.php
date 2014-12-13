<?php
/**
*  Contemplate
*  Light-weight Template Engine for PHP, Python, Node and client-side JavaScript
*
*  @version: 0.7.1
*  https://github.com/foo123/Contemplate
*
*  @inspired by : Simple JavaScript Templating, John Resig - http://ejohn.org/ - MIT Licensed
*  http://ejohn.org/blog/javascript-micro-templating/
*
**/
if (!class_exists('Contemplate'))
{

// can use inline templates for plugins etc.. to enable non-linear plugin compile-time replacement
class ContemplateInlineTemplate
{ 
    public $id = null;
    public $tpl = '';
    private $_renderer = null;
    
    public static function multisplit( $tpl, $reps=array(), $as_array=false ) 
    {
        //$as_array = !Contemplate::is_assoc( $reps );
        $a = array( array(1, $tpl) );
        foreach ( (array)$reps as $r=>$s )
        {
            $c = array( ); $sr = $as_array ? $s : $r; $s = array( 0, $s );  $al = count($a);
            for ($i=0; $i<$al; $i++)
            {
                if ( 1 === $a[ $i ][ 0 ] )
                {
                    $b = explode($sr, $a[ $i ][ 1 ]); $bl = count($b);
                    $c[] = array(1, $b[0]);
                    if ( $bl > 1 )
                    {
                        for ($j=0; $j<$bl-1; $j++)
                        {
                            $c[] = $s;
                            $c[] = array(1, $b[$j+1]);
                        }
                    }
                }
                else
                {
                    $c[] = $a[ $i ];
                }
            }
            $a = $c;
        }
        return $a;
    }
    
    public static function compile( $tpl ) 
    {
        $l = count($tpl);
        $out = 'return (';
        for ($i=0; $i<$l; $i++)
        {
            $notIsSub = $tpl[ $i ][ 0 ]; $s = $tpl[ $i ][ 1 ];
            if ( $notIsSub ) $out .= "'" . preg_replace(Contemplate::$NEWLINE, "' . \"\\n\" . '", preg_replace(Contemplate::$SQUOTE, "\\'", $s)) . "'";
            else $out .= " . \$args['" . $s . "'] . ";
        }
        $out .= ');';
        return create_function('$args', $out);
    }
    
    public function __construct( $tpl='', $replacements=null, $compiled=false ) 
    {
        if ( !$replacements ) $replacements = array();
        $this->id = null;
        $this->_renderer = null;
        $this->tpl = self::multisplit( $tpl, (array)$replacements );
        if ( true === $compiled )
        {
            $this->_renderer = self::compile( $this->tpl );
        }
    }
    
    public function dispose( ) 
    {
        $this->id = null;
        $this->tpl = null;
        $this->_renderer = null;
        return $this;
    }
    
    public function render( $args=null ) 
    {
        if ( !$args ) $args = array();
        if ( $this->_renderer )
        {
            $renderer = $this->_renderer;
            return $renderer( $args );
        }
        
        $tpl =& $this->tpl; 
        $l = count($tpl);
        $args = (array)$args;
        $out = '';
        for ($i=0; $i<$l; $i++)
        {
            $notIsSub = $tpl[ $i ][ 0 ]; $s = $tpl[ $i ][ 1 ];
            $out .= ($notIsSub ? $s : $args[ $s ]);
        }
        return $out;
    }
}
class ContemplateTemplate
{ 
    public $id = null;
    public $d = null;
    protected $_extends = null;
    protected $_blocks = null;
    protected $_renderer = null;
    
    
    public function __construct( $id=null )
    {
        /* initialize internal vars */
        $this->id = null; 
        $this->d = null;
        $this->_renderer = null;
        $this->_extends = null;
        $this->_blocks = null;
        if ( $id ) 
        { 
            $this->id = $id; 
        }
    }
    
    public function dispose( ) 
    {
        $this->_extends = null;
        $this->_blocks = null;
        $this->_renderer = null;
        $this->d = null;
        $this->id = null;
        return $this;
    }
    
    public function setId( $id=null ) 
    { 
        if ( $id ) $this->id = $id; 
        return $this; 
    }
    
    public function extend( $tpl ) 
    { 
        if ( $tpl && is_string($tpl) )
            $this->_extends = Contemplate::tpl( $tpl );
        else
            $this->_extends = $tpl; 
        return $this; 
    }
    
    public function setBlocks( $blocks ) 
    { 
        if ( !$this->_blocks ) $this->_blocks = array(); 
        $this->_blocks = Contemplate::merge( $this->_blocks, $blocks ); 
        return $this; 
    }
    
    public function setRenderFunction( $renderFunc=null ) 
    { 
        if ( $renderFunc ) $this->_renderer = $renderFunc; 
        return $this; 
    }
    
    public function renderBlock( $block, $__i__=null )
    {
        if ( !$__i__ ) $__i__ = $this;
        
        if ( $this->_blocks && isset($this->_blocks[$block]) ) 
        {
            $blockfunc = $this->_blocks[$block]; 
            return $blockfunc( $__i__ );
        }
        elseif ( $this->_extends ) 
        {
            return $this->_extends->renderBlock($block, $__i__);
        }
        return '';
    }
    
    public function render( $data, $__i__=null ) 
    {
        $__p__ = ''; 
        if ( !$__i__ ) $__i__ = $this;
        
        if ( $this->_extends ) 
        { 
            $__p__ = $this->_extends->render($data, $__i__); 
        }
        elseif ( $this->_renderer )
        {
            /* dynamic function */
            $__i__->d =& $data; 
            $renderer = $this->_renderer;
            $__p__ = $renderer( $__i__ );
        }
        
        $this->d = null;
        return $__p__;
    }
}

class Contemplate
{
    const VERSION = "0.7.1";
    
    const CACHE_TO_DISK_NONE = 0;
    const CACHE_TO_DISK_AUTOUPDATE = 2;
    const CACHE_TO_DISK_NOUPDATE = 4;
    
    public static $ALPHA = '/^[a-zA-Z_]/';
    public static $NUM = '/^[0-9]/';
    public static $ALPHANUM = '/^[a-zA-Z0-9_]/';
    public static $SPACE = '/^\\s/';
    public static $NEWLINE = '/\\n\\r|\\r\\n|\\n|\\r/';
    public static $SQUOTE = "/'/";
    
    private static $__isInited = false;
    private static $__cacheDir = './';
    private static $__cacheMode = 0;
    private static $__cache = array();
    private static $__templates = array();
    private static $__partials = array();
    private static $__locale = array();
    private static $__plurals = array();
    
    private static $__leftTplSep = "<%";
    private static $__rightTplSep = "%>";
    private static $__preserveLinesDefault = "' . \"\\n\" . '";
    private static $__preserveLines = '';
    private static $__escape = true;
    private static $__EOL = "\n";
    private static $__TEOL = PHP_EOL;
    private static $__tplStart = '';
    private static $__tplEnd = '';
    private static $__tplPrefixCode = '';
    
    private static $__pad = "    ";
    private static $__level = 0;
    private static $__loops = 0;
    private static $__ifs = 0;
    private static $__loopifs = 0;
    private static $__allblocks = null;
    private static $__allblockscnt = null;
    private static $__openblocks = null;
    private static $__startblock = null; 
    private static $__endblock = null; 
    private static $__blockptr = -1;
    private static $__extends = null;
    private static $__stack = null;
    private static $__uuid = 0;
    private static $__idcnt = 0;
    private static $__locals; 
    private static $__variables;
    private static $__currentblock;
    
    private static $TT_ClassCode = null;

    private static $TT_BlockCode = null;
    private static $TT_BLOCK = null;
    
    private static $TT_IF = null;
    private static $TT_ELSEIF = null;
    private static $TT_ELSE = null;
    private static $TT_ENDIF = null;
    
    private static $TT_FOR1 = null;
    private static $TT_FOR2 = null;
    private static $TT_ELSEFOR = null;
    private static $TT_ENDFOR1 = null;
    private static $TT_ENDFOR2 = null;
    
    private static $TT_FUNC1 = null;
    private static $TT_FUNC2 = null;
    private static $TT_RCODE1 = null;
    private static $TT_RCODE2 = null;
    
    private static $__regExps = array(
        'specials' => null,
        'replacements' => null,
        'functions' => null,
        'controls' => null,
        'controls2' => null
    );
    
    private static $__controlConstructs = array(
        'include', 'template', 
        'extends', 'endblock', 'block',
        'elsefor', 'endfor', 'for',
        'set', 'unset', 'isset',
        'elseif', 'else', 'endif', 'if'
    );
    
    private static $__funcs = array( 
        'htmlselect', 'htmltable',
        '(plg_|plugin_)([a-zA-Z0-9_]+)', 'haskey', 
        'lowercase', 'uppercase', 'camelcase', 'snakecase', 'pluralise',
        'concat', 'ltrim', 'rtrim', 'trim', 'sprintf', 'addslashes', 'stripslashes',
        'inline', 'tpl', 'uuid',
        'html', 'url', 'count', 
        'ldate', 'date', 'now', 'locale',
        'dq', 'q', 'l', 's', 'n', 'f', 'e' 
    );
    
    private static $__plugins = array();
    
    //
    //
    //
    
    public static function Template( $id=null )
    {
        return new ContemplateTemplate( $id );
    }
    
    public static function InlineTemplate( $tpl, $reps=array(), $compiled=false )
    {
        return new ContemplateInlineTemplate( $tpl, $reps, $compiled );
    }
    
    /*public static function is_assoc( $arr )
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }*/


    public static function init( )
    {
        if ( self::$__isInited ) return;
        
        // pre-compute the needed regular expressions
        self::$__regExps[ 'specials' ] = '/[\\n\\r\\v\\t]/';
        
        self::$__regExps[ 'replacements' ] = '/\\t[ ]*(.*?)[ ]*\\v/';
        
        self::$__regExps[ 'controls' ] = '/\\t[ ]*%(' . implode('|', self::$__controlConstructs) . ')\\b[ ]*\\((.*)\\)/';
        self::$__regExps[ 'controls2' ] = '/%(' . implode('|', self::$__controlConstructs) . ')\\b[ ]*\\((.*)\\)/';
        
        self::$__regExps[ 'functions' ] = '/%(' . implode('|', self::$__funcs) . ')\\b/';
        
        self::$__preserveLines = self::$__preserveLinesDefault;
        
        self::$__tplStart = "'; " . self::$__TEOL;
        self::$__tplEnd = self::$__TEOL . "\$__p__ .= '";
        
        // make compilation templates
        self::$TT_ClassCode = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit(implode("#EOL#", array(
            "#PREFIXCODE#"
            ,""
            ,"/* Contemplate cached template '#TPLID#' */"
            ,"if (!class_exists('#CLASSNAME#'))"
            ,"{"
            ,"final class #CLASSNAME# extends ContemplateTemplate"
            ,"{    "
            ,"    /* constructor */"
            ,"    public function __construct(\$id=null)"
            ,"    {"
            ,"        /* initialize internal vars */"
            ,"        \$this->id = null; "
            ,"        \$this->d = null;"
            ,"        \$this->_renderer = null;"
            ,"        \$this->_extends = null;"
            ,"        \$this->_blocks = null;"
            ,"        "
            ,"        \$this->id = \$id;"
            ,"        "
            ,"        /* extend tpl assign code starts here */"
            ,"#EXTENDCODE#"
            ,"        /* extend tpl assign code ends here */"
            ,"    }    "
            ,"    "
            ,"    /* tpl-defined blocks render code starts here */"
            ,"#BLOCKS#"
            ,"    /* tpl-defined blocks render code ends here */"
            ,"    "
            ,"    /* tpl renderBlock method */"
            ,"    public function renderBlock( \$block, \$__i__=null )"
            ,"    {"
            ,"        \$__p__ = '';"
            ,"        if ( !\$__i__ ) \$__i__ = \$this;"
            ,"        "
            ,"        \$method = '_blockfn_' . \$block;"
            ,"        if ( method_exists(\$this, \$method) ) return \$this->{\$method}(\$__i__);"
            ,"        elseif ( \$this->_extends ) return \$this->_extends->renderBlock(\$block, \$__i__);"
            ,"        return \$__p__;"
            ,"    }"
            ,"    "
            ,"    /* tpl render method */"
            ,"    public function render(\$data, \$__i__=null)"
            ,"    {"
            ,"        \$__p__ = '';"
            ,"        if ( !\$__i__ ) \$__i__ = \$this;"
            ,"        "
            ,"        if ( \$this->_extends )"
            ,"        {"
            ,"            \$__p__ = \$this->_extends->render(\$data, \$__i__);"
            ,"        }"
            ,"        else"
            ,"        {"
            ,"            /* tpl main render code starts here */"
            ,"#RENDERCODE#"
            ,"            /* tpl main render code ends here */"
            ,"        }"
            ,"        \$this->d = null;"
            ,"        return \$__p__;"
            ,"    }"
            ,"}"
            ,"}"
            ,""
        )), array(
             "#EOL#"=>            "EOL"
            ,"#PREFIXCODE#"=>     "PREFIXCODE"
            ,"#CLASSNAME#"=>      "CLASSNAME"
            ,"#TPLID#"=>          "TPLID"
            ,"#BLOCKS#"=>         "BLOCKS"
            ,"#EXTENDCODE#"=>     "EXTENDCODE"
            ,"#RENDERCODE#"=>     "RENDERCODE"
        )));
        
        self::$TT_BlockCode = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit(implode("#EOL#", array(
            ""
            ,"/* tpl block render method for block '#BLOCKNAME#' */"
            ,"private function #BLOCKMETHODNAME#(\$__i__) "
            ,"{ "
            ,"#BLOCKMETHODCODE#"
            ,"}"
            ,""
        )), array(
             "#EOL#"=>                  "EOL"
            ,"#BLOCKNAME#"=>            "BLOCKNAME"
            ,"#BLOCKMETHODNAME#"=>      "BLOCKMETHODNAME"
            ,"#BLOCKMETHODCODE#"=>      "BLOCKMETHODCODE"
        )));
        
        self::$TT_BLOCK = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit(implode("#EOL#", array(
            ""
            ,"\$__p__ = ''; \$data =& \$__i__->d;"
            ,"#BLOCKCODE#"
            ,"return \$__p__;"
            ,""
        )), array(
             "#EOL#"=>      "EOL"
            ,"#BLOCKCODE#"=>   "BLOCKCODE"
        )));
        
        self::$TT_IF = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit(implode("#EOL#", array(
            ""
            ,"if (#IFCOND#)"
            ,"{"
            ,""
        )), array(
             "#EOL#"=>      "EOL"
            ,"#IFCOND#"=>   "IFCOND"
        )));
        
        self::$TT_ELSEIF = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit(implode("#EOL#", array(
            ""
            ,"}"
            ,"elseif (#ELIFCOND#)"
            ,"{"
            ,""
        )), array(
             "#EOL#"=>      "EOL"
            ,"#ELIFCOND#"=>   "ELIFCOND"
        )));

        self::$TT_ELSE = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit(implode("#EOL#", array(
            ""
            ,"}"
            ,"else"
            ,"{"
            ,""
        )), array(
             "#EOL#"=>      "EOL"
        )));
        
        self::$TT_ENDIF = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit(implode("#EOL#", array(
            ""
            ,"}"
            ,""
        )), array(
             "#EOL#"=>      "EOL"
        )));
        
        self::$TT_FOR2 = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit(implode("#EOL#", array(
            ""
            ,"#_O# = #O#;"
            ,"if (!empty(#_O#))"
            ,"{"
            ,"    foreach (#_O# as #K#=>#V#)"
            ,"    {"
            //,"        #ASSIGN1#"
            ,""
        )), array(
             "#EOL#"=>      "EOL"
            ,"#O#"=>   "O"
            ,"#_O#"=>   "_O"
            ,"#K#"=>   "K"
            ,"#V#"=>   "V"
        )));
        self::$TT_FOR1 = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit(implode("#EOL#", array(
            ""
            ,"#_O# = #O#;"
            ,"if (!empty(#_O#))"
            ,"{"
            ,"    foreach (#_O# as #V#)"
            ,"    {"
            //,"        #ASSIGN1#"
            ,""
        )), array(
             "#EOL#"=>      "EOL"
            ,"#O#"=>   "O"
            ,"#_O#"=>   "_O"
            ,"#V#"=>   "V"
        )));
        
        self::$TT_ELSEFOR = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit(implode("#EOL#", array(
            ""
            ,"    }"
            ,"}"
            ,"else"
            ,"{"
            ,""
        )), array(
             "#EOL#"=>      "EOL"
        )));
        
        self::$TT_ENDFOR2 = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit(implode("#EOL#", array(
            ""
            ,"}"
            ,""
        )), array(
             "#EOL#"=>      "EOL"
        )));
        self::$TT_ENDFOR1 = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit(implode("#EOL#", array(
            ""
            ,"    }"
            ,"}"
            ,""
        )), array(
             "#EOL#"=>      "EOL"
        )));
        
        self::$TT_FUNC1 = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit("return '';"));
        self::$TT_FUNC2 = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit(implode("#EOL#", array(
            ""
            ,"\$__p__ = ''; \$data =& \$__i__->d;"  
            ,"#FCODE#"
            ,"return \$__p__;"
            ,""
        )), array(
             "#EOL#"=>      "EOL"
            ,"#FCODE#"=>   "FCODE"
        )));

        self::$TT_RCODE1 = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit("\$__p__ = '';"));
        self::$TT_RCODE2 = ContemplateInlineTemplate::compile(ContemplateInlineTemplate::multisplit(implode("#EOL#", array(
            ""
            ,"\$__i__->d =& \$data;" 
            ,"#RCODE#"
            ,""
        )), array(
             "#EOL#"=>      "EOL"
            ,"#RCODE#"=>   "RCODE"
        )));
        
        self::clearState();
        
        self::$__isInited = true;
    }
    
    // http://www.blainesch.com/403/dynamically-adding-methods-to-classes-and-objects-in-php/
    public static function __callstatic( $method, $params=array() ) 
    {
        if ( isset( self::$__plugins[ $method ] ) && is_callable( self::$__plugins[ $method ] ) ) 
        {
            return call_user_func_array(self::$__plugins[ $method ], $params);
        } 
        /*else 
        {
            throw new Exception("Method not defined.");
        }*/
        return '';
    }
    
    //
    // Main template static methods
    //
    
    // add custom plugins as template functions
    public static function addPlugin( $name, $pluginCode ) 
    {
        self::$__plugins[ $name ] = $pluginCode;
    }
    
    // custom php code to add to start of template (eg custom access checks etc..)
    public static function setPrefixCode( $preCode=null )
    {
        if ( $preCode )
            self::$__tplPrefixCode = (string)$preCode;
    }
    
    public static function setLocaleStrings( $l ) 
    { 
        self::$__locale = self::merge(self::$__locale, $l); 
    }
    
    public static function clearLocaleStrings( ) 
    { 
        self::$__locale = array(); 
    }
    
    public static function setPlurals( $plurals ) 
    { 
        if ( is_array($plurals) )
        {
            foreach ($plurals as $singular=>$plural)
            {
                if ( null == $plural )
                {
                    // auto plural
                    $plurals[ $singular ] = $singular.'s';
                }
            }
            self::$__plurals = self::merge(self::$__plurals, $plurals); 
        }
    }
    
    public static function clearPlurals( ) 
    { 
        self::$__plurals = array(); 
    }
    
    public static function setTemplateSeparators( $seps=null )
    {
        if (is_array($seps))
        {
            if ( isset($seps['left']) ) self::$__leftTplSep = strval($seps['left']);
            if ( isset($seps['right']) ) self::$__rightTplSep = strval($seps['right']);
        }
    }
    
    public static function setPreserveLines( $enable=true ) 
    { 
        if ( $enable )  
            self::$__preserveLines = self::$__preserveLinesDefault;  
        else 
            self::$__preserveLines = ''; 
    }
    
    public static function setCacheDir( $dir ) 
    {  
        self::$__cacheDir = rtrim($dir,'/').'/'; 
    }
    
    public static function setCacheMode( $mode ) 
    { 
        self::$__cacheMode = $mode; 
    }
    
    public static function clearCache( $all=false ) 
    { 
        self::$__cache = array(); 
        if ( $all ) self::$__partials = array(); 
    }
    
    // add templates manually
    public static function add( $tpls, $tplStr=null ) 
    { 
        if ( is_array($tpls) )
        {
            foreach ($tpls as $tplID=>$tplData)
            {
                if ( is_array( $tplData ) )
                {
                    // unified way to add tpls both as reference and inline
                    // inline tpl, passed as array
                    if ( isset($tplData[ 0 ]) )
                        self::$__templates[ $tplID ] = array($tplData[ 0 ], true);
                }
                else
                {
                    self::$__templates[ $tplID ] = array($tpls[ $tplID ], false); 
                }
            }
        }
        elseif ( $tpls && $tplStr )
        {
            if ( is_array( $tplStr ) )
            {
                // unified way to add tpls both as reference and inline
                // inline tpl, passed as array
                if ( isset($tplStr[ 0 ]) )
                    self::$__templates[ $tpls ] = array($tplStr[ 0 ], true);
            }
            else
            {
                self::$__templates[ $tpls ] = array($tplStr, false);
            }
        }
    }
    
    public static function parseTpl( $tpl, $options=array() ) 
    {
        if ( $options && !empty($options['separators']) )
            $separators = $options['separators'];
        else $separators = null;
        
        if ( $separators )
        {
            $tmp = array(self::$__leftTplSep, self::$__rightTplSep);
            self::$__leftTplSep = $separators[ 0 ];  self::$__rightTplSep = $separators[ 1 ];
        }
        
        self::resetState();
        $parsed = self::parse( $tpl );
        self::clearState();
        
        if ( $separators )
        {
            self::$__leftTplSep = $tmp[ 0 ]; self::$__rightTplSep = $tmp[ 1 ];
        }
        
        return $parsed;
    }
        
    // return the requested template (with optional data)
    public static function tpl( $tpl, $data=null, $options=array() )
    {
        if ( $tpl instanceof ContemplateTemplate )
        {
            // Provide some basic currying to the user
            if ( is_array( $data ) )  return $tpl->render( $data );
            else  return $tpl;
        }
        
        $options = array_merge(array(
            'autoUpdate'=> false,
            'refresh'=> false,
            'escape'=> true,
            'separators'=> null
        ), (array)$options);
        
        if ( false === $options['escape'] ) self::$__escape = false;
        else  self::$__escape = true;
        
        // Figure out if we're getting a template, or if we need to
        // load the template - and be sure to cache the result.
        if ( $options['refresh'] || !isset(self::$__cache[ $tpl ]) ) 
        {
            // load/parse required tpl (and any associated tpl)
            self::$__cache[ $tpl ] = self::getCachedTemplate( $tpl, $options );
        }
        
        $tmpl = self::$__cache[ $tpl ];
        
        // Provide some basic currying to the user
        if ( is_array( $data ) )  return $tmpl->render( $data );
        else  return $tmpl;
    }
    
    //
    // Basic template functions
    //
    
    // inline tpls, both inside Contemplate templates (i.e as parameters) and in code
    public static function inline( $tpl, $reps=array(), $compiled=true )
    {
        if ( $tpl && ($tpl instanceof ContemplateInlineTemplate) ) return $tpl->render( (array)$reps );
        return new ContemplateInlineTemplate( $tpl, $reps, $compiled );
    }
    
    // check if (nested) keys exist in tpl variable
    public static function haskey( $v/*, key1, key2, etc.. */ ) 
    {
        if (!$v || !is_array($v)) return false;
        $args = func_get_args();
        $argslen = count($args);
        $tmp = $v;
        for ($i=1; $i<$argslen; $i++)
        {
            if (!array_key_exists($args[$i], $tmp)) return false;
            $tmp = $tmp[$args[$i]];
        }
        return true;
    }
        
    // quote
    public static function q( $e ) 
    { 
        return "'" . $e . "'"; 
    }
    
    // double quote
    public static function dq( $e ) 
    { 
        return '"' . $e . '"'; 
    }
    
    // to String
    public static function s( $e ) 
    { 
        return strval($e); 
    }
    
    // to Integer
    public static function n( $e ) 
    { 
        return intval($e); 
    }
    
    // to Float
    public static function f( $e ) 
    { 
        return floatval($e); 
    }
    
    // basic custom faster html escaping
    public static function e( $s ) 
    {
        return str_replace(array('&', '<', '>', '"', '\''), array('&amp;', '&lt;', '&gt;', '&quot;', '&#39;'), $s);
    }
        
    // basic html escaping
    public static function html( $s, $mode=ENT_COMPAT ) 
    { 
        return htmlentities($s, $mode, 'UTF-8'); 
    }
    
    // basic url escaping
    public static function url( $s ) 
    { 
        return urlencode($s); 
    }
    
    // count items in array
    public static function count( $a ) 
    { 
        return count($a); 
    }
    
    public static function addslashes( $s )
    {
        return addslashes($s);
    }
    
    public static function stripslashes( $s )
    {
        return stripslashes($s);
    }
    
    // Concatenate strings/vars
    public static function concat( ) 
    { 
        $args = func_get_args(); 
        return implode('', $args); 
    }
    
    // Trim strings in templates
    public static function trim( $s, $charlist=null ) 
    { 
        if ( $charlist ) return trim($s, $charlist); 
        else return trim($s); 
    }
    
    public static function ltrim( $s, $charlist=null ) 
    { 
        if ( $charlist ) return ltrim($s, $charlist); 
        else return ltrim($s); 
    }
    
    public static function rtrim( $s, $charlist=null ) 
    { 
        if ( $charlist ) return rtrim($s, $charlist); 
        else return rtrim($s); 
    }
    
    public static function ucfirst( $s )
    {
        return ucfirst($s);
    }
    
    public static function lcfirst( $s )
    {
        return lcfirst($s);
    }
    
    public static function lowercase( $s )
    {
        return strtolower($s);
    }
    
    public static function uppercase( $s )
    {
        return strtoupper($s);
    }
    
    public static function camelcase( $s, $sep="_", $capitalizeFirst=false )
    {
        if ( $capitalizeFirst )
            return implode("", array_map("ucfirst", explode($sep, $s))); 
        else
            return lcfirst( implode("", array_map("ucfirst", explode($sep, $s))) );
    }
    
    public static function snakecase( $s, $sep="_" )
    {
        return strtolower( preg_replace( '/([A-Z])/', $sep . '$1', $s ) );
    }
    
    // Sprintf in templates
    public static function sprintf( ) 
    { 
        $args = func_get_args(); 
        $format = array_shift($args); 
        return vsprintf($format, $args); 
    }
    
    //
    //  Localization functions
    //
    
    // current time in seconds
    public static function time( ) 
    { 
        return time(); 
    }
    public static function now( ) 
    { 
        return time(); 
    }
    
    // formatted date
    public static function date( $format, $time=false ) 
    { 
        if (false===$time) $time=time(); 
        return date($format, $time); 
    }
    
    // localized formatted date
    public static function ldate( $format, $time=false ) 
    { 
        if (false===$time) $time=time(); 
        return self::_localized_date(self::$__locale, $format, $time);  
    }
    
    // locale
    public static function locale( $e ) 
    { 
        return ( isset(self::$__locale[$e]) ) ? self::$__locale[$e] : $e; 
    }
    public static function l( $e ) 
    { 
        return self::locale($e); 
    }
    // pluralise
    public static function pluralise( $singular, $count ) 
    { 
        if ( isset(self::$__plurals[$singular]) )
            return 1 != $count ? self::$__plurals[$singular] : $singular;
        return $singular;
    }
    
    // generate a uuid
    public static function uuid( $namespace='UUID' ) 
    {
        return implode('_', array($namespace, ++self::$__uuid, time()));
    }
    
    //
    //  HTML elements
    //
    
    // html table
    public static function htmltable( $data, $options=array() )
    {
        $data=(array)$data;
        $options=(array)$options;
        
        $hasRowTpl = isset($options['tpl_row']);
        $hasCellTpl = isset($options['tpl_cell']);
        $rowTpl = null; $cellTpl = null;
        
        if ( $hasRowTpl )
        {
            if ( !($options['tpl_row'] instanceof ContemplateInlineTemplate) )
                $options['tpl_row'] = new ContemplateInlineTemplate($options['tpl_row'], array('$odd'=>'odd','$row'=>'row'));
            $rowTpl = $options['tpl_row'];
        }
        if ( $hasCellTpl )
        {
            if ( !($options['tpl_cell'] instanceof ContemplateInlineTemplate) )
                $options['tpl_cell'] = new ContemplateInlineTemplate($options['tpl_cell'], array('$cell'=>'cell'));
            $cellTpl = $options['tpl_cell'];
        }
            
        $o="<table";
        
        if (isset($options['id']))  $o.=" id='{$options['id']}'";
        if (isset($options['class'])) $o.=" class='{$options['class']}'";
        if (isset($options['style']))  $o.=" style='{$options['style']}'";
        if (isset($options['data']))
        {
            foreach ((array)$options['data'] as $k=>$v)
                $o.=" data-{$k}='{$v}'";
        }
        $o.=">";
            
        $tk='';
        if (
            (isset($options['header']) && $options['header']) || 
            (isset($options['footer']) && $options['footer'])
        )
            $tk="<td>".implode('</td><td>', @array_keys($data))."</td>";
            
        $header='';
        if (isset($options['header']) && $options['header'])
            $header="<thead><tr>{$tk}</tr></thead>";
            
        $footer='';
        if (isset($options['footer']) && $options['footer'])
            $footer="<tfoot><tr>{$tk}</tr></tfoot>";
        
        $o.=$header;
        
        // get data rows
        $rows=array();
        foreach (@array_values($data) as $i=>$col)
        {
            foreach (@array_values((array)$col) as $j=>$d)
            {
                if (!isset($rows[$j])) $rows[$j]=array_fill(0, count($col), '');
                $rows[$j][$i]=$d;
            }
        }
        
        if (isset($options['odd']))
            $class_odd=$options['odd'];
        else
            $class_odd='odd';
        if (isset($options['even']))
            $class_even=$options['even'];
        else
            $class_even='even';
            
        // render rows
        $odd=false;
        foreach (@$rows as $row1)
        {
            $row_class = $odd ? $class_odd : $class_even;
            
            if ( $hasCellTpl )
            {
                $row = ''; $rl = count($row1);
                for ($r=0; $r<$rl; $r++)
                    $row .= $cellTpl->render( array('cell'=> $row1[$r]) );
            }
            else
            {
                $row = "<td>".implode('</td><td>', $row1)."</td>";
            }
            if ( $hasRowTpl )
            {
                $o .= $rowTpl->render( array('odd'=> $row_class, 'row'=> $row) );
            }
            else
            {
                $o .= "<tr class='" . $row_class . "'>".$row."</tr>";
            }
            
            $odd=!$odd;
        }
        unset($rows);
        
        $o.=$footer;
        $o.="</table>";
        return $o;
    }
    
    // html select
    public static function htmlselect( $data, $options=array() )
    {
        $data=(array)$data;
        $options=(array)$options;
        
        $hasOptionTpl = isset($options['tpl_option']); 
        $optionTpl = null;
        
        if ( $hasOptionTpl )
        {
            if ( !($options['tpl_option'] instanceof ContemplateInlineTemplate) )
                $options['tpl_option'] = new ContemplateInlineTemplate($options['tpl_option'], array('$selected'=>'selected','$value'=>'value','$option'=>'option'));
            $optionTpl = $options['tpl_option'];
        }
            
        $o="<select";
        
        if (isset($options['multiple']) && $options['multiple']) $o.=" multiple";
        if (isset($options['disabled']) && $options['disabled']) $o.=" disabled='disabled'";
        if (isset($options['name'])) $o.=" name='{$options['name']}'";
        if (isset($options['id'])) $o.=" id='{$options['id']}'";
        if (isset($options['class']))  $o.=" class='{$options['class']}'";
        if (isset($options['style'])) $o.=" style='{$options['style']}'";
        if (isset($options['data']))
        {
            foreach ((array)$options['data'] as $k=>$v)
                $o.=" data-{$k}='{$v}'";
        }
        $o.=">";
        
        if (isset($options['selected']))
            $options['selected']=array_flip((array)$options['selected']);
        else
            $options['selected']=array();
            
        if (isset($options['optgroups']))
            $options['optgroups']=array_flip((array)$options['optgroups']);
    
        foreach ($data as $k=>$v)
        {
            if (isset($options['optgroups']) && isset($options['optgroups'][$k]))
            {
                $o.="<optgroup label='{$k}'>";
                foreach ((array)$v as $k2=>$v2)
                {
                    if (isset($options['use_key']))  $v2=$k2;
                    elseif (isset($options['use_value'])) $k2=$v2;
                        
                    if ( $hasOptionTpl )
                        $o .= $optionTpl->render(array(
                            'value'=> $k2,
                            'option'=> $v2,
                            'selected'=> array_key_exists($k2, $options['selected']) ? ' selected="selected"' : ''
                        ));
                    elseif (/*isset($options['selected'][$k2])*/ array_key_exists($k2, $options['selected']))
                        $o .= "<option value='{$k2}' selected='selected'>{$v2}</option>";
                    else
                        $o .= "<option value='{$k2}'>{$v2}</option>";
                }
                $o.="</optgroup>";
            }
            else
            {
                if (isset($options['use_key'])) $v=$k;
                elseif (isset($options['use_value'])) $k=$v;
                    
                if ( $hasOptionTpl )
                    $o .= $optionTpl->render(array(
                        'value'=> $k,
                        'option'=> $v,
                        'selected'=> array_key_exists($k, $options['selected']) ? ' selected="selected"' : ''
                    ));
                elseif (isset($options['selected'][$k]))
                    $o .= "<option value='{$k}' selected='selected'>{$v}</option>";
                else
                    $o .= "<option value='{$k}'>{$v}</option>";
            }
        }
        $o.="</select>";
        return $o;
    }
    
    //
    // Control structures
    //
    
    // whether var is set
    private static function t_isset( $varname ) 
    {
        return ' (isset(' . $varname . ')) ';
    }
        
    // set/create/update tpl var
    private static function t_set( $args ) 
    {
        $args = explode(',', $args);
        $varname = trim( array_shift($args) );
        $expr = trim(implode(',', $args));
        return "';" . self::$__TEOL . self::padLines( "$varname = ( $expr );" ) . self::$__TEOL;
    }
    
    // unset/remove/delete tpl var
    private static function t_unset( $varname=null ) 
    {
        if ( $varname && strlen($varname) )
        {
            $varname = trim( $varname );
            return "';" . self::$__TEOL . self::padLines( "if (isset($varname)) unset( $varname );" ) . self::$__TEOL;
        }
        return "'; " . self::$__TEOL; 
    }
        
    // if
    private static function t_if( $cond='false' ) 
    {  
        $renderer = self::$TT_IF;
        $out = "';" . self::padLines( $renderer(array(
                'EOL'=>     self::$__TEOL,
                'IFCOND'=> $cond
            )) );
        self::$__ifs++;  
        self::$__level++;
        
        return $out;
    }
    
    // elseif    
    private static function t_elseif( $cond='false' ) 
    { 
        $renderer = self::$TT_ELSEIF;
        self::$__level--;
        $out = "';" . self::padLines( $renderer(array(
                'EOL'=>     self::$__TEOL,
                'ELIFCOND'=> $cond
            )) );
        self::$__level++;
        
        return $out;
    }
    
    // else
    private static function t_else( ) 
    { 
        $renderer = self::$TT_ELSE;
        self::$__level--;
        $out = "';" . self::padLines( $renderer(array( 
            'EOL'=>     self::$__TEOL
        )) );
        self::$__level++;
        
        return $out;
    }
    
    // endif
    private static function t_endif( ) 
    { 
        $renderer = self::$TT_ENDIF;
        self::$__ifs--;  
        self::$__level--;
        $out = "';" . self::padLines( $renderer(array( 
            'EOL'=>     self::$__TEOL
        )) );
        
        return $out;
    }
    
    // for, foreach
    private static function t_for( $for_expr ) 
    {
        $is_php_style = strpos($for_expr, ' as ');
        $is_python_style = strpos($for_expr, ' in ');
        
        if ( false !== $is_python_style )
        {
            $for_expr = array(substr($for_expr, 0, $is_python_style), substr($for_expr, $is_python_style+4));
            $o = trim($for_expr[1]); 
            $_o = '$_loc_' . (++self::$__idcnt);
            $kv = explode(',', $for_expr[0]); 
        }
        else /*if ( false !== $is_php_style )*/
        {
            $for_expr = array(substr($for_expr, 0, $is_php_style), substr($for_expr, $is_php_style+4));
            $o = trim($for_expr[0]); 
            $_o = '$_loc_' . (++self::$__idcnt);
            $kv = explode('=>', $for_expr[1]); 
        }
        $isAssoc = (count($kv) >= 2);
        
        if ( $isAssoc )
        {
            $k = trim($kv[0]); 
            $v = trim($kv[1]); 

            self::$__locals[self::$__currentblock][self::$__variables[self::$__currentblock][$k]] = 1; 
            self::$__locals[self::$__currentblock][self::$__variables[self::$__currentblock][$v]] = 1;
            $renderer = self::$TT_FOR2;
            $out = "';" . self::padLines( $renderer(array(
                    'EOL'=>     self::$__TEOL,
                    'O'=> $o, '_O'=> $_o, 
                    'K'=> $k, 'V'=> $v
                    //,'ASSIGN1'=> ""
                )) );
            self::$__level+=2;
        }
        else
        {
            $v = trim($kv[0]); 

            self::$__locals[self::$__currentblock][self::$__variables[self::$__currentblock][$v]] = 1;
            $renderer = self::$TT_FOR1;
            $out = "';" . self::padLines( $renderer(array(
                    'EOL'=>     self::$__TEOL,
                    'O'=> $o, '_O'=> $_o, 
                    'V'=> $v
                    //,'ASSIGN1'=> ""
                )) );
            self::$__level+=2;
        }
        self::$__loops++;  self::$__loopifs++;
        
        return $out;
    }
    
    // elsefor
    private static function t_elsefor( ) 
    { 
        /* else attached to  for loop */ 
        $renderer = self::$TT_ELSEFOR;
        self::$__loopifs--;  
        self::$__level+=-2;
        $out = "';" . self::padLines( $renderer(array( 
            'EOL'=>     self::$__TEOL
        )) );
        self::$__level+=1;
        
        return $out;
    }
    
    // endfor
    private static function t_endfor( ) 
    {
        if ( self::$__loopifs == self::$__loops ) 
        { 
            self::$__loops--; self::$__loopifs--;  
            self::$__level+=-2;
            $renderer = self::$TT_ENDFOR1;
            $out = "';" . self::padLines( $renderer( array(
                'EOL'=>     self::$__TEOL
            ) ) );
        }
        else
        {
            self::$__loops--;  
            self::$__level+=-1;
            $renderer = self::$TT_ENDFOR2;
            $out = "';" . self::padLines( $renderer( array(
                'EOL'=>     self::$__TEOL
            ) ) );
        }
        return $out;
    }
    
    // include file
    private static function t_include( $id ) 
    { 
        /* cache it */ 
        if ( !isset(self::$__partials[$id]) )
        {
            self::pushState();
            self::resetState();
            self::$__partials[$id]=" " . self::parse(self::getSeparators( self::getTemplateContents($id) ), false) . "'; " . self::$__TEOL;
            self::popState();
        }
        return self::padLines( self::$__partials[$id] );
    }
    
    // include template
    private static function t_template( $args )
    {
        $args = explode(',', $args); 
        $id = trim(array_shift($args));
        $obj = implode(',', $args);
        return '\' . %tpl( "'.$id.'", '.$obj.' ); ' . self::$__TEOL;
    }
    
    // extend another template
    private static function t_extends( $tpl ) 
    { 
        self::$__extends = trim( $tpl );
        return "'; " . self::$__TEOL; 
    }
    
    // define (overridable) block
    private static function t_block( $block ) 
    { 
        $block = trim( $block );
        array_push(self::$__allblocks, array($block, -1, -1, 0, self::$__openblocks[ 0 ][ 1 ]));
        self::$__allblockscnt[ $block ] = isset(self::$__allblockscnt[ $block ]) ? (self::$__allblockscnt[ $block ]+1) : 1;
        self::$__blockptr = count(self::$__allblocks);
        array_unshift(self::$__openblocks, array($block, self::$__blockptr-1));
        self::$__startblock = $block;
        self::$__endblock = null;
        self::$__currentblock = $block;
        if ( !isset(self::$__locals[self::$__currentblock]) ) self::$__locals[self::$__currentblock] = array();
        if ( !isset(self::$__variables[self::$__currentblock]) ) self::$__variables[self::$__currentblock] = array();
        return "' .  __||" . $block . "||__";  
    }
    
    // end define (overridable) block
    private static function t_endblock( ) 
    { 
        if ( 1 < count(self::$__openblocks) ) 
        {
            $block = array_shift(self::$__openblocks);
            self::$__endblock = $block[0];
            self::$__blockptr = $block[1]+1;
            self::$__startblock = null;
            self::$__currentblock = empty(self::$__openblocks) ? '_' : self::$__openblocks[0][0];
            return "__||/" . $block[0] . "||__";
        }
        else
        {
            self::$__currentblock = '_';
        }
        return '';  
    }

    //
    // auxilliary parsing methods
    //
    private static function split( $s, $leftTplSep, $rightTplSep )
    {
        $parts1 = explode( $leftTplSep, $s );
        $len = count( $parts1 );
        $parts = array();
        for ($i=0; $i<$len; $i++)
        {
            $tmp = explode( $rightTplSep, $parts1[$i] );
            $parts[] = $tmp[0];
            if ( isset($tmp[1]) ) $parts[] = $tmp[1];
        }
        return $parts;
    }
    
    private static function parseControlConstructs( $m )
    {
        if (isset($m[1]))
        {
            $ctrl = $m[1];
            $args = isset($m[2]) ? $m[2] : '';
            
            switch($ctrl)
            {
                case 'isset': 
                    // constructs in args, eg. isset
                    $args = preg_replace_callback( self::$__regExps['controls2'], array(__CLASS__, 'parseControlConstructs'), $args );
                    return self::t_isset($args);  
                    break;
                
                case 'set':  
                    // constructs in args, eg. isset
                    $args = preg_replace_callback( self::$__regExps['controls2'], array(__CLASS__, 'parseControlConstructs'), $args );
                    return self::t_set($args);  
                    break;
                
                case 'unset': 
                    // constructs in args, eg. isset
                    $args = preg_replace_callback( self::$__regExps['controls2'], array(__CLASS__, 'parseControlConstructs'), $args );
                    return self::t_unset($args);  
                    break;
                
                case 'if': 
                    // constructs in args, eg. isset
                    $args = preg_replace_callback( self::$__regExps['controls2'], array(__CLASS__, 'parseControlConstructs'), $args );
                    return self::t_if($args);  
                    break;
                
                case 'elseif': 
                    // constructs in args, eg. isset
                    $args = preg_replace_callback( self::$__regExps['controls2'], array(__CLASS__, 'parseControlConstructs'), $args );
                    return self::t_elseif($args); 
                    break;
                
                case 'else': 
                    return self::t_else($args);  
                    break;
                
                case 'endif': 
                    return self::t_endif($args);  
                    break;
                
                case 'for': 
                    // constructs in args, eg. isset
                    $args = preg_replace_callback( self::$__regExps['controls2'], array(__CLASS__, 'parseControlConstructs'), $args );
                    return self::t_for($args);  
                    break;
                
                case 'elsefor': 
                    return self::t_elsefor($args); 
                    break;
                
                case 'endfor': 
                    return self::t_endfor($args); 
                    break;
                
                case 'template': 
                    // constructs in args, eg. isset
                    $args = preg_replace_callback( self::$__regExps['controls2'], array(__CLASS__, 'parseControlConstructs'), $args );
                    return self::t_template($args); 
                    break;
                
                case 'extends': 
                    return self::t_extends($args); 
                    break;
                
                case 'block': 
                    return self::t_block($args); 
                    break;
                
                case 'endblock': 
                    return self::t_endblock($args); 
                    break;
                
                case 'include': 
                    return self::t_include($args); 
                    break;
            }
        }
        return $m[0];
    }
    
    private static function parseBlocks( $s ) 
    {
        $blocks = array(); 
        $bl = count(self::$__allblocks);
        $renderer = self::$TT_BLOCK;
        while ($bl--)
        {
            $delims = self::$__allblocks[ $bl ];
            
            $block = $delims[ 0 ];
            $pos1 = $delims[ 1 ];
            $pos2 = $delims[ 2 ];
            $off = $delims[ 3 ];
            $containerblock = $delims[ 4 ];
            $tag = "__||" . $block . "||__";
            $rep = "\$__i__->renderBlock( '" . $block . "' ); ";
            $tl = strlen($tag); $rl = strlen($rep);
            
            if ( -1 < $containerblock )
            {
                // adjust the ending position of the container block (if nested)
                // to compensate for the replacements in this (nested) block
                self::$__allblocks[ $containerblock ][ 3 ] += $rl - ($pos2-$pos1+1);
            }
            // adjust the ending position of this block (if nested)
            // to compensate for the replacements of any (nested) block(s)
            $pos2 += $off;
            
            if ( 1 === self::$__allblockscnt[ $block ] )
            {
                // 1st occurance, block definition
                array_push($blocks, array($block, $renderer(array(
                        'EOL'=>     self::$__TEOL,
                        'BLOCKCODE'=> substr($s, $pos1+$tl, $pos2-$tl-1-$pos1-$tl) ."';"
                    ))));
            }
            /*
                function strSlice($str, $start, $end) {
                    $end = $end - $start;
                    return substr($str, $start, $end);
                }
            */
            $s = substr($s, 0, $pos1) . $rep . substr($s, $pos2+1);
            if ( 1 <= self::$__allblockscnt[ $block ] ) self::$__allblockscnt[ $block ]--;
        }
        //self::$__allblocks = null; self::$__allblockscnt = null; self::$__openblocks = null;
        
        return array($s, $blocks);
    }

    private static function parseString( $s, $q, $i, $l )
    {
        $string = $q;
        $escaped = false;
        $ch = '';
        while ( $i < $l )
        {
            $ch = $s[$i++];
            $string .= $ch;
            if ( $q == $ch && !$escaped )  break;
            $escaped = (!$escaped && '\\' == $ch);
        }
        return $string;
    }
    
    private static function parseVariable( $s, $i, $l )
    {
        if ( preg_match(self::$ALPHA, $s[$i], $m) )
        {
            $strings = array();
            $variables = array();
            
            // main variable
            $variable = $s[$i++];
            while ( $i < $l && preg_match(self::$ALPHANUM, $s[$i], $m) )
            {
                $variable .= $s[$i++];
            }
            
            $variable_raw = $variable;
            // transform into tpl variable
            $variable_main = "\$data['" . $variable_raw . "']";
            $variable_rest = "";
            self::$__idcnt++;
            $id = "__##VAR" . self::$__idcnt . "##__";
            $len = strlen($variable_raw);
            
            // extra space
            $space = 0;
            while ( $i < $l && preg_match(self::$SPACE, $s[$i], $m) )
            {
                $space++;
                $i++;
            }
            
            $bracketcnt = 0;
            
            // optional properties
            while ( $i < $l && ('.' == $s[$i] || '[' == $s[$i]) )
            {
                $delim = $s[$i++];
                
                // extra space
                while ( $i < $l && preg_match(self::$SPACE, $s[$i], $m) )
                {
                    $space++;
                    $i++;
                }
            
                // alpha-numeric dot property
                if ( '.' == $delim )
                {
                    // property
                    $property = '';
                    while ( $i < $l && preg_match(self::$ALPHANUM, $s[$i], $m) )
                    {
                        $property .= $s[$i++];
                    }
                    $lp = strlen($property);
                    if ( $lp )
                    {
                        // transform into tpl variable bracketed property
                        $variable_rest .= "['" . $property . "']";
                        $len += $space + 1 + $lp;
                        $space = 0;
                    }
                    else
                    {
                        break;
                    }
                }
                
                // bracketed property
                elseif ( '[' == $delim )
                {
                    $bracketcnt++;
                    
                    $ch = $s[$i];
                    
                    // literal string property
                    if ( '"' == $ch || "'" == $ch )
                    {
                        $property = self::parseString( $s, $ch, $i+1, $l );
                        self::$__idcnt++;
                        $strid = "__##STR" .self::$__idcnt . "##__";
                        $strings[$strid] = $property;
                        $variable_rest .= $delim . $strid;
                        $lp = strlen($property);
                        $i += $lp;
                        $len += $space + 1 + $lp;
                        $space = 0;
                    }
                    
                    // numeric array property
                    elseif ( preg_match(self::$NUM, $ch, $m) )
                    {
                        $property = $s[$i++];
                        while ( $i < $l && preg_match(self::$NUM, $s[$i], $m) )
                        {
                            $property .= $s[$i++];
                        }
                        $variable_rest .= $delim . $property;
                        $lp = strlen($property);
                        $len += $space + 1 + $lp;
                        $space = 0;
                    }
                    
                    // sub-variable property
                    elseif ( '$' == $ch )
                    {
                        $sub = substr($s, $i+1);
                        $subvariables = self::parseVariable($sub, 0, strlen($sub));
                        if ( $subvariables )
                        {
                            // transform into tpl variable property
                            $property = end($subvariables);
                            $variable_rest .= $delim . $property[0];
                            $lp = $property[4];
                            $i += $lp + 1;
                            $len += $space + 2 + $lp;
                            $space = 0;
                            $variables = array_merge($variables, $subvariables);
                        }
                    }
                    
                    // close bracket
                    elseif ( ']' == $ch )
                    {
                        if ( $bracketcnt > 0 )
                        {
                            $bracketcnt--;
                            $variable_rest .= $delim . $s[$i++];
                            $len += $space + 2;
                            $space = 0;
                        }
                        else
                        {
                            break;
                        }
                    }
                    
                    else
                    {
                        break;
                    }
                    
                    
                    // extra space
                    while ( $i < $l && preg_match(self::$SPACE, $s[$i], $m) )
                    {
                        $space++;
                        $i++;
                    }
            
                    // close bracket
                    if ( ']' == $s[$i] )
                    {
                        if ( $bracketcnt > 0 )
                        {
                            $bracketcnt--;
                            $variable_rest .= $s[$i++];
                            $len += $space + 1;
                            $space = 0;
                        }
                        else
                        {
                            break;
                        }
                    }
                }
                
                // extra space
                while ( $i < $l && preg_match(self::$SPACE, $s[$i], $m) )
                {
                    $space++;
                    $i++;
                }
            }
            
            $variables[] = array($id, $variable_raw, $variable_main, $variable_rest, $len, $strings);
            return $variables;
        }
        return null;
    }
    
    private static function funcReplace( $m )
    {
        $plugin = !empty($m[3]) ? $m[3] : null; 
        if ( $plugin && isset(self::$__plugins[$plugin]) )
        {
            $pl = self::$__plugins[$plugin];
            
            if ( $pl instanceof ContemplateInlineTemplate )
            {
                return $pl->render( );
            }
            else
            {
                self::$__plugins['plg_' . $plugin] = $pl;
                unset(self::$__plugins[$plugin]);
                return 'Contemplate::plg_' . $plugin; 
            }
        }
        else
        {
            return 'Contemplate::' . $m[1];
        }
    }
    
    private static function parse( $tpl, $withblocks=true ) 
    {
        $parts = self::split($tpl, self::$__leftTplSep, self::$__rightTplSep);
        $len = count($parts);
        $isTag = false;
        $parsed = '';
        for ($i=0; $i<$len; $i++)
        {
            $s = $parts[$i];
            
            if ( $isTag )
            {
                $tag = "\t" . preg_replace( self::$__regExps['specials'], " ", $s ) . "\v"; // replace special chars
                
                // parse each template tag section accurately
                // refined parsing of strings and variables
                $count = strlen( $tag );
                $index = 0;
                $ch = '';
                $out = '';
                $variables = array();
                $strings = array();
                while ( $index < $count )
                {
                    $ch = $tag[$index++];
                    
                    // parse mainly literal strings and variables
                    
                    // literal string
                    if ( '"' == $ch || "'" == $ch )
                    {
                        $tok = self::parseString( $tag, $ch, $index, $count );
                        self::$__idcnt++;
                        $id = "__##STR" . self::$__idcnt . "##__";
                        $strings[ $id ] = $tok;
                        $out .= $id;
                        $index += strlen($tok)-1;
                    }
                    // variable
                    elseif ( '$' == $ch )
                    {
                        $tok = self::parseVariable($tag, $index, $count);
                        if ( $tok )
                        {
                            foreach ($tok as $tokv)
                            {
                                $id = $tokv[ 0 ];
                                self::$__variables[self::$__currentblock][ $id ] = $tokv[ 1 ];
                                $strings = array_merge( $strings, $tokv[ 5 ] );
                            }
                            $out .= $id;
                            $index += $tokv[ 4 ];
                            $variables = array_merge( $variables, $tok );
                        }
                        else
                        {
                            $out .= '$';
                        }
                    }
                    // rest, bypass
                    else
                    {
                        $out .= $ch;
                    }
                }
                $tag = $out;
                
                // fix literal data notation
                $tag = str_replace(array('{', '}', '[', ']', ':'), array('array(', ')','array(', ')', '=>'), $tag);
                
                self::$__startblock = null;  self::$__endblock = null; self::$__blockptr = -1;
                // directives and control constructs
                $tag = preg_replace_callback( self::$__regExps['controls'], array(__CLASS__, 'parseControlConstructs'), $tag );
                
                // functions
                $tag = preg_replace_callback( self::$__regExps['functions'], array(__CLASS__, 'funcReplace')/*'Contemplate::${1}'*/, $tag );
                
                // other replacements
                $tag = preg_replace( self::$__regExps['replacements'], '\' . ( $1 ) . \'', $tag );
                
                // replace variables
                $lr = count($variables);
                for($v=$lr-1; $v>=0; $v--)
                {
                    $id = $variables[ $v ][ 0 ]; $varname = $variables[ $v ][ 1 ];
                    $tag = str_replace( $id.'__RAW__', $varname, $tag );
                    if ( isset(self::$__locals[self::$__currentblock][$varname]) ) /* local (loop) variable */
                        $tag = str_replace( $id, '$_loc_' . $varname . $variables[ $v ][ 3 ], $tag );
                    else /* default (data) variable */
                        $tag = str_replace( $id, $variables[ $v ][ 2 ] . $variables[ $v ][ 3 ], $tag );
                }
                // replace strings (accurately)
                $tagTpl = ContemplateInlineTemplate::multisplit($tag, array_keys($strings), 1);
                $tag = '';
                foreach ($tagTpl as $v)
                {
                    $tag .= $v[0] ? $v[1] : $strings[ $v[1] ];
                }
                
                $tag = str_replace( array("\t", "\v"), array(self::$__tplStart, self::padLines( self::$__tplEnd )), $tag );
                
                $s  = $tag;
                
                if ( self::$__startblock )
                {
                    self::$__startblock = "__||".self::$__startblock."||__";
                    self::$__allblocks[ self::$__blockptr-1 ][ 1 ] = strlen($parsed) + strpos($tag, self::$__startblock);
                }
                elseif ( self::$__endblock )
                {
                    self::$__endblock = "__||/".self::$__endblock."||__";
                    self::$__allblocks[ self::$__blockptr-1 ][ 2 ] = strlen($parsed) + strpos($tag, self::$__endblock) + strlen(self::$__endblock);
                }
                    
                $isTag = false;
            }
            else
            {
                if ( self::$__escape )
                    $s = str_replace( "\\", "\\\\", $s );  // escape escapes
                
                $s = str_replace( "'", "\\'", $s );  // escape single quotes accurately (used by parse function)
                
                $s = preg_replace( "/[\n]/", self::$__preserveLines, $s ); // preserve lines
                
                $isTag = true;
            }
            
            $parsed .= $s;
        }
        
        if ( false !== $withblocks ) 
        {
            if ( !empty(self::$__allblocks) ) return self::parseBlocks($parsed);
            else return array($parsed, array());
        }
        
        return $parsed;
    }
    
    private static function getSeparators( $text, $separators=null )
    {
        if ( $separators )
        {
            $seps = explode( " ", trim( $separators ) );
            self::$__leftTplSep = trim( $seps[ 0 ] );
            self::$__rightTplSep = trim( $seps[ 1 ] );
        }
        else
        {
            // tpl separators are defined on 1st (non-empty) line of tpl content
            $lines = explode( "\n", $text );
            while ( count($lines)>0 && !strlen( trim( $lines[ 0 ] ) ) ) array_shift( $lines );
            if ( count($lines)>0 )
            {
                $seps = explode( " ", trim( array_shift( $lines ) ) );
                self::$__leftTplSep = trim( $seps[ 0 ] );
                self::$__rightTplSep = trim( $seps[ 1 ] );
            }
            $text = implode("\n", $lines);
        }
        return $text;
    }
    
    public static function getTemplateContents( $id )
    {
        if ( isset(self::$__templates[$id]) )
        {
            $template = self::$__templates[$id];
            if ( $template[1] ) return $template[0]; // inline tpl
            elseif ( is_file($template[0]) ) return file_get_contents( $template[0] );
        }
        return '';
    }
    
    private static function getCachedTemplateName( $id ) 
    { 
        return self::$__cacheDir . preg_replace('/[\\W]+/', '_', $id) . '_tpl.php'; 
    }
    
    private static function getCachedTemplateClass( $id ) 
    { 
        return 'Contemplate_' .  preg_replace('/[\\W]+/', '_', $id) . '_Cached';  
    }
    
    private static function createTemplateRenderFunction( $id, $seps=null )
    {
        self::resetState();
        
        $blocks = self::parse(self::getSeparators( self::getTemplateContents($id), $seps ));
        
        self::clearState();
        
        $renderf = $blocks[0];
        $blocks = $blocks[1];
        $bl = count($blocks);
        
        if ( self::$__extends )
        {
            $renderer = self::$TT_FUNC1;
            $func = $renderer(array(
                        'EOL'=>     self::$__TEOL
                    ));
        }
        else
        {
            $renderer = self::$TT_FUNC2;
            $func = $renderer(array(
                        'EOL'=>     self::$__TEOL,
                        'FCODE'=> "\$__p__ .= '" . $renderf . "';"
                    ));
        }
        
        $fn = create_function('$__i__', $func);
        
        $blockfns = array();  
        for($b=0; $b<$bl; $b++) 
        {
            $blockfns[$blocks[$b][0]] = create_function('$__i__', $blocks[$b][1]);
        }
        
        return array($fn, $blockfns);
    }
    
    private static function createCachedTemplate( $id, $filename, $classname, $seps=null )
    {
        self::resetState();
        
        $blocks = self::parse(self::getSeparators( self::getTemplateContents($id), $seps ));
        
        self::clearState();
        
        $renderf = $blocks[0];
        $blocks = $blocks[1];
        $bl = count($blocks);
        
        // tpl-defined blocks
        $renderer = self::$TT_BlockCode;
        $sblocks = '';
        for($b=0; $b<$bl; $b++) 
        {
            $sblocks .= self::$__TEOL . $renderer(array(
                            "EOL"=>    self::$__TEOL,
                            'BLOCKNAME'=> $blocks[$b][0],
                            'BLOCKMETHODNAME'=> "_blockfn_" . $blocks[$b][0],
                            'BLOCKMETHODCODE'=> self::padLines($blocks[$b][1], 1)
                        ));
        }
        
        // tpl render code
        if (self::$__extends)
        {
            $extendCode = "\$this->extend( '".self::$__extends."' );";
            $renderer = self::$TT_RCODE1;
            $renderCode = $renderer(array(
                            'EOL'=>     self::$__TEOL
                        ));
        }
        else
        {
            $extendCode = '';
            $renderer = self::$TT_RCODE2;
            $renderCode = $renderer(array(
                            'EOL'=>     self::$__TEOL,
                            'RCODE'=> "\$__p__ .= '" . $renderf . "';"
                        ));
        }
        
        if ( self::$__tplPrefixCode ) $prefixCode = self::$__tplPrefixCode;
        else  $prefixCode = '';
            
        // generate tpl class
        $renderer = self::$TT_ClassCode;
        $class = '<?php ' .self::$__TEOL . $renderer(array(
                                "EOL"=>    self::$__TEOL,
                                'PREFIXCODE'=> $prefixCode,
                                'TPLID'=> $id,
                                'CLASSNAME'=> $classname,
                                'EXTENDCODE'=> self::padLines($extendCode, 2),
                                'BLOCKS'=> self::padLines($sblocks, 1),
                                'RENDERCODE'=> self::padLines($renderCode, 3)
                            ));
        
        //return self::setCachedTemplate($filename, $class);
        return file_put_contents($filename, $class);
    }
    
    private static function getCachedTemplate( $id, $options=array() )
    {
        if ( isset(self::$__templates[$id]) )
        {
            $template = self::$__templates[$id];
            // inline templates saved only in-memory
            if ( $template[1] )
            {
                // dynamic in-memory caching during page-request
                //return new Contemplate($id, self::createTemplateRenderFunction($id));
                $tpl = new ContemplateTemplate(); $tpl->setId( $id );
                if ( isset($options['parsed']) && is_string($options['parsed']) )
                {
                    // already parsed code was given
                    $tpl->setRenderFunction( create_function('$__i__', $options['parsed']) ); 
                }
                else
                {
                    $fns = self::createTemplateRenderFunction($id, $options['separators']);
                    $tpl->setRenderFunction( $fns[0] ); $tpl->setBlocks( $fns[1] );
                }
                if ( self::$__extends ) $tpl->extend( self::tpl(self::$__extends, null, false) );
                return $tpl;
            }
            
            else
            {
                if ( true !== $options['autoUpdate'] && self::CACHE_TO_DISK_NOUPDATE === self::$__cacheMode )
                {
                    $cachedTplFile = self::getCachedTemplateName($id);
                    $cachedTplClass = self::getCachedTemplateClass($id);
                    if ( !is_file($cachedTplFile) )
                    {
                        // if not exist, create it
                        self::createCachedTemplate($id, $cachedTplFile, $cachedTplClass, $options['separators']);
                    }
                    if (is_file($cachedTplFile))
                    {
                        include($cachedTplFile);  
                        $tpl = new $cachedTplClass();
                        $tpl->setId( $id ); 
                        return $tpl;
                    }
                    return null;
                }
                
                elseif ( true === $options['autoUpdate'] || self::CACHE_TO_DISK_AUTOUPDATE === self::$__cacheMode )
                {
                    $cachedTplFile = self::getCachedTemplateName($id);
                    $cachedTplClass = self::getCachedTemplateClass($id);
                    if ( !is_file($cachedTplFile) || (filemtime($cachedTplFile) <= filemtime($template[0])) )
                    {
                        // if tpl not exist or is out-of-sync (re-)create it
                        self::createCachedTemplate($id, $cachedTplFile, $cachedTplClass, $options['separators']);
                    }
                    if ( is_file($cachedTplFile) )
                    {
                        include($cachedTplFile);  
                        $tpl = new $cachedTplClass();
                        $tpl->setId( $id );  
                        return $tpl;
                    }
                    return null;
                }
                
                else
                {
                    // dynamic in-memory caching during page-request
                    //return new Contemplate($id, self::createTemplateRenderFunction($id));
                    $tpl = new ContemplateTemplate();
                    $tpl->setId( $id );
                    $fns = self::createTemplateRenderFunction($id, $options['separators']);
                    $tpl->setRenderFunction( $fns[0] ); 
                    $tpl->setBlocks( $fns[1] );
                    if ( self::$__extends ) $tpl->extend( self::tpl(self::$__extends) );
                    return $tpl;
                }
            }
        }
        return null;
    }
    
    private static function setCachedTemplate( $filename, $tplContents ) 
    { 
        return file_put_contents($filename, $tplContents); 
    }
    
    private static function resetState( ) 
    {
        // reset state
        self::$__loops = 0; self::$__ifs = 0; self::$__loopifs = 0; self::$__level = 0;
        self::$__allblocks = array(); self::$__allblockscnt = array(); self::$__openblocks = array(array(null, -1));  
        self::$__extends = null; self::$__locals = array(); self::$__variables = array(); self::$__currentblock = '_';
        if ( !isset(self::$__locals[$__currentblock]) ) self::$__locals[$__currentblock] = array();
        if ( !isset(self::$__variables[$__currentblock]) ) self::$__variables[$__currentblock] = array();
        //self::$__escape = true;
    }
    
    private static function clearState( ) 
    {
        // clear state
        self::$__loops = 0; self::$__ifs = 0; self::$__loopifs = 0; self::$__level = 0;
        self::$__allblocks = null; self::$__allblockscnt = null; self::$__openblocks = null;
        /*self::$__extends = null;*/ self::$__locals = null; self::$__variables = null; self::$__currentblock = null;
        self::$__idcnt = 0; self::$__stack = array();
    }
    
    private static function pushState( ) 
    {
        // push state
        array_push(self::$__stack, array(self::$__loops, self::$__ifs, self::$__loopifs, self::$__level,
        self::$__allblocks, self::$__allblockscnt, self::$__openblocks,  self::$__extends, self::$__locals, self::$__variables, self::$__currentblock));
    }
    
    private static function popState( ) 
    {
        // pop state
        $t = array_pop(self::$__stack);
        self::$__loops = $t[0]; self::$__ifs = $t[1]; self::$__loopifs = $t[2]; self::$__level = $t[3];
        self::$__allblocks = $t[4]; self::$__allblockscnt = $t[5]; self::$__openblocks = $t[6];
        self::$__extends = $t[7]; self::$__locals = $t[8]; self::$__variables = $t[9]; self::$__currentblock = $t[10];
    }
    
    private static function _localized_date( $locale, $format, $timestamp ) 
    {
        $txt_words = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sun", "Mon", "Tues", "Wednes", "Thurs", "Fri", "Satur", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
        
        $date = date($format, $timestamp);
        
        // localize days/months
        $replace = array();
        
        foreach ($txt_words as $word) 
            if ( isset($locale[$word]) ) 
                $replace[$word] = $locale[$word]; 
            
        if ( !empty($replace) )  
            $date = str_replace( array_keys($replace), array_values($replace), $date );
            
        // return localized date
        return $date;
    }
    
    private static function padLines( $lines, $level=null )
    {
        if ( null === $level )  $level = self::$__level;
        
        //$NLRX = '/\n\r|\r\n|\n|\r/';
        
        if ($level>=0)
        {
            //$pad=implode("", array_fill(0, $level, "    "));
            $pad = str_repeat(self::$__pad, $level);
            $lines = $pad . (implode( self::$__TEOL . $pad, preg_split(self::$NEWLINE, $lines) ));
        }
        
        return $lines;
    }
    
    /*private static function j( ) /* joinLines * /
    {
        $args = func_get_args( );
        return implode(self::$__TEOL, $args);
    }*/
    
    public static function data($d)
    {
        if ( $d instanceof \stdClass ) 
        {
            $d = (array)$d; //array_merge(array(), (array)$d);
        }
        if ( is_array($d) ) 
        { 
            foreach ($d as $k=>$v) 
            { 
                if ( $v instanceof \stdClass || is_array($v) ) $d[$k] = self::data($v); 
            } 
        }
        return $d;
    }
    
    
    public static function keys( $o ) 
    {
        if ( $o ) return array_keys( $o );
        return null;
    }
    
    public static function values( $o ) 
    { 
        if ( $o ) return array_values( $o );
        return null;
    }
        
    public static function items( $o ) 
    { 
        if ( $o ) return $o;
        return null;
    }
        
    public static function merge()
    {
        if (func_num_args() < 1) return;
        $arrays = func_get_args(); 
        $merged = array_shift($arrays); 
        $isTargetObject = false;
        
        if ( is_object($merged) ) 
        { 
            $isTargetObject = true; 
            $merged = (array)$merged; 
        }
        
        foreach ($arrays as $arr)
        {
            $isObject = false;
            if ( is_object($arr) ) 
            { 
                $isObject = true; 
                $arr = (array)$arr; 
            }
                
            foreach($arr as $key => $val)
            {
                // only one level, no need to recurse
                /*if( array_key_exists($key, $merged) && (is_array($val) || is_object($val)) )
                {
                    $merged[$key] = self::merge($merged[$key], $arr[$key]);
                    if (is_object($val))  $merged[$key] = (object)$merged[$key];
                }
                else*/  $merged[$key] = $val;
            }
        }
        if ($isTargetObject) 
        { 
            $isTargetObject = false; 
            $merged = (object)$merged; 
        }
        return $merged;
    }
}

// init the engine on first load
Contemplate::init();
}
