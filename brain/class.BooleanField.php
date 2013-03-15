<?php

class BooleanField extends Field
{
    protected $Type = 'TINYINT';
    protected $Primary = false;
    protected $Lenght = 1;
    protected $default_value = 0;
    
    public function __construct($name)
    {
        parent::__construct($name);
    }
}