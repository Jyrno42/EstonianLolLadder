<?php

class IntField extends Field
{
    protected $Type = 'INT';
    protected $Primary = false;
    protected $Lenght = 11;
    protected $default_value = 0;
    
    public function __construct($name, $len)
    {
        parent::__construct($name);
        $this->Lenght = $len;
    }
}