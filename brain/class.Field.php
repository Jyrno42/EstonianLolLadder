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
    
    private function MigrateType($t, $l)
    {
        return $l !== null ?
                sprintf("%s(%d)", $t, $l) :
                sprintf("%s", $t);
    }
    private function MigrateDefault()
    {
        if ($this->default_value !== NULL)
        {
            return "DEFAULT " .
                    sprintf(is_string($this->default_value) ? "'%s'" : "%s", $this->default_value);
        }
        return '';
    }
    public function Migrate($table_name)
    {
        if ($table_name)
        {
            return sprintf("ALTER TABLE %s ADD %s %s %s", 
                        $table_name,
                        $this->FieldName,
                        $this->MigrateType($this->Type, $this->Lenght),
                        $this->MigrateDefault());
        }
        else
        {
            return sprintf("%s %s %s",
                        $this->FieldName,
                        $this->MigrateType($this->Type, $this->Lenght),
                        $this->MigrateDefault());
        }
    }
};