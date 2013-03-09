<?php

class User extends Models 
{
    public $UserID;
    public $Email;
    public $passkey;
    public $Rights;
    public $Name;

    private static $Fields = null;
    public static function ModelFields()
    {
        if(!self::$Fields)
        {
            self::$Fields = (object)null;
            self::$Fields->UserID = new PrimaryKey("UserID");
            self::$Fields->Email = new TextField("Email", 128);
            self::$Fields->passkey = new TextField("passkey", 128);
            self::$Fields->Rights = new TextField("Rights", 32);
            self::$Fields->Name = new TextField("Name", 64);
        }
        return self::$Fields;
    }
    protected static function class_name()
    {
        return __CLASS__; 
    }
}
