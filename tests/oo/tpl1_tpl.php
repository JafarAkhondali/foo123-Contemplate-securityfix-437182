<?php 

/* Contemplate cached template 'tpl1' */
if (!class_exists('Contemplate_tpl1_Cached'))
{
final class Contemplate_tpl1_Cached extends ContemplateTemplate
{    
    /* constructor */
    public function __construct($id=null)
    {
        $self = $this;
        /* initialize internal vars */
        $self->_renderer = null;
        $self->_extends = null;
        $self->_blocks = null;
        $self->id = null;
        $self->id = $id;
        
        /* extend tpl assign code starts here */
        
        /* extend tpl assign code ends here */
    }    
    
    /* tpl-defined blocks render code starts here */
    
    
    /* tpl block render method for block '3' */
    private function _blockfn_3(&$data, $self, $__i__) 
    { 
        
        $__p__ = '';
        
        $__p__ .= '(1 3)';
        return $__p__;
        
    }
    
    
    /* tpl block render method for block '2' */
    private function _blockfn_2(&$data, $self, $__i__) 
    { 
        
        $__p__ = '';
        
        $__p__ .= '(1 2)' . "\n" . '        ' .  $__i__->renderBlock('3', $data);
        $__p__ .= '' . "\n" . '    ';
        return $__p__;
        
    }
    
    
    /* tpl block render method for block '1' */
    private function _blockfn_1(&$data, $self, $__i__) 
    { 
        
        $__p__ = '';
        
        $__p__ .= '(1 1)' . "\n" . '    ' .  $__i__->renderBlock('2', $data);
        $__p__ .= '' . "\n" . '';
        return $__p__;
        
    }
    
    /* tpl-defined blocks render code ends here */
    
    /* tpl renderBlock method */
    public function renderBlock($block, &$data, $__i__=null)
    {
        $self = $this;
        if ( !$__i__ ) $__i__ = $self;
        $method = '_blockfn_' . $block;
        if ( method_exists($self, $method) ) return $self->{$method}($data, $self, $__i__);
        elseif ( $self->_extends ) return $self->_extends->renderBlock($block, $data, $__i__);
        return '';
    }
    
    /* tpl render method */
    public function render(&$data, $__i__=null)
    {
        $self = $this;
        if ( !$__i__ ) $__i__ = $self;
        $__p__ = '';
        if ( $self->_extends )
        {
            $__p__ = $self->_extends->render($data, $__i__);
        }
        else
        {
            /* tpl main render code starts here */
            
            $__p__ .= '' .  $__i__->renderBlock('1', $data);
            $__p__ .= '';
            
            /* tpl main render code ends here */
        }
        return $__p__;
    }
}
}
