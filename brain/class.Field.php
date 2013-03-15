<?php

class Field
{
    protected $Type = 'INT';
    protected $Primary = false;
    protected $Lenght = 11;
    protected $default_value = 0;
    
    protected $FieldName;
    public function __construct($name)
    {
        $this->FieldName = $name;
    }
    
    public function get_primary()
    {
        return $this->Primary;
    }
    
    public function Migrate($table_name)
    {
        $query = sprintf("ALTER TABLE %s ADD %s %s(%d) %s", $table_name, $this->FieldName, $this->Type, $this->Lenght, $this->default_value !== NULL ? "DEFAULT " . $this->default_value : "");
        return $query;
    }
};