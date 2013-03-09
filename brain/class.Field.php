<?php

class Field
{
    protected $Type = 'INT';
    protected $Primary = false;
    protected $Lenght = 11;
    
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
        $query = sprintf("ALTER TABLE %s ADD %s %s(%d)", $table_name, $this->FieldName, $this->Type, $this->Lenght);
        return $query;
    }
};