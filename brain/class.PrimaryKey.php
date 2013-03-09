<?php

class PrimaryKey extends Field
{
    protected $Type = 'INT';
    protected $Primary = true;
    protected $Lenght = 11;
    
    public function __construct($name)
    {
        parent::__construct($name);
    }
};

class ForeignKey extends Field
{
    protected $Type = 'INT';
    protected $Lenght = 11;
    
    public function __construct($name)
    {
        parent::__construct($name);
    }
};