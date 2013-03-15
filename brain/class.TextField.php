<?php

class TextField extends Field
{
    protected $Type = 'VARCHAR';
    protected $Primary = false;
    protected $Lenght = 128;
    protected $default_value = '';
    
    public function __construct($name, $len)
    {
        parent::__construct($name);
        $this->Lenght = $len;
    }
}