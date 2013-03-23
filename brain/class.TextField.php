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

class LongTextField extends Field
{
    protected $Type = 'LONGTEXT';
    protected $Primary = false;
    protected $Lenght = null;
    protected $default_value = '';
    
    public function __construct($name)
    {
        parent::__construct($name);
    }
}