<?php

class Models 
{
    public static function model_class() {
        return static::class_name();
    }

    public function asd($ob, $db) 
    {
        /*$primary = 0;
        foreach($ob as $k => $v)
        {
            if(is_object($v) && get_parent_class($v) == "Field")
            {
                if($v->get_primary())
                {
                    $primary++;
                }
                $this->model_Fields[$k] = $k;
            }
        }
        if($primary != 1)
            throw new Exception(sprintf("Model %s needs primary key", get_class($ob)));
        
        $this->model_table_name = strtolower(get_class($ob) . "s");
        $this->model_DB = $db;
        
        if(sizeof($this->model_Fields) < 1)
            throw new Exception(sprintf("Model %s needs to have some fields.", get_class($ob)));*/
    }
    
    static function table_name($class) {
        return (defined("TABLE_PREFIX") ? TABLE_PREFIX : "") . strtolower($class . "s");
    }

    public static function save(&$model, $db)
    {
        $id = null;
        $cname = get_class($model);
        $fields = array();
        
        // Check if id field is provided.
        // Also store new values
        foreach($cname::ModelFields() as $k => $v)
        {
            if(is_object($v) && get_parent_class($v) == "Field")
            {
                if($v->get_primary())
                {
                    $id = $k;
                }
                if($model->$k)
                {
                    $fields[$k] = $model->$k;
                }
            }
        }
        
        $table = strtolower($cname . "s");
        if($id && isset($model->$id) && $model->$id !== FALSE)
        {
            // do a select query to verify we have this element
            $q = new QueryObject($table, null, $cname, $db);
            if(($ret = $q->filter(array($id => $model->$id))->get(1)) !== null)
            {
                // do a update query
                $query = sprintf("UPDATE %s SET %s WHERE %s = '%s'",
                    (defined("TABLE_PREFIX") ? TABLE_PREFIX : "") . $table,
                    QueryObject::smart_implode($fields, ", ", 
                        function ($k, $v, $last, $db)
                        {
                            return sprintf("%s = '%s'", $k, $db->mysql_escape_string($v));
                        }
                    , $db),
                    $id,
                    $model->$id
                );
                return $query;
                return $db->mysql_query($query) !== FALSE;
            }
        }
        
        try
        {
            $query = sprintf("INSERT INTO %s(%s) VALUES(%s)",
                (defined("TABLE_PREFIX") ? TABLE_PREFIX : "") . $table,
                QueryObject::smart_implode($fields, ", ", 
                    function ($k, $v, $last, $db)
                    {
                        return sprintf("%s", $db->mysql_escape_string($k));
                    }
                , $db),
                QueryObject::smart_implode($fields, ", ", 
                    function ($k, $v, $last, $db)
                    {
                        return sprintf("'%s'", $db->mysql_escape_string($v));
                    }
                , $db)
            );
            if($db->mysql_query($query) !== FALSE)
            {
                $model->$id = $db->mysql_insert_id();
                return true;
            }
        }
        catch(Exception $e)
        {
        }
        return false;
        // else do a insert query
    }

    public static function objects($db)
    {
        $class_name = self::model_class();
        $model_table_name = strtolower($class_name . "s");
        
        return new QueryObject($model_table_name, $class_name::ModelFields(), $class_name, $db);
    }
}

class QueryObject
{
    const SELECT = 'SELECT %1$s %2$s FROM %3$s %8$s %4$s %5$s %6$s %7$s';
    
    private $table_name;
    private $table_fields;
    private $class_name;
    
    private $query;
    private $DB;
    
    public function QueryObject($table, $fields, $cName, $db)
    {
        $this->DB = $db;
        $this->table_name = (defined("TABLE_PREFIX") ? TABLE_PREFIX : "") . $table;
        $this->table_fields = $fields;
        $this->class_name = $cName;
    }

    public static function smart_implode($array, $glue=", ", $callback=null, $extra=null)
    {
        if(!is_array($array))
        {
            return $array;
        }
        else
        {
            $ret = "";
            foreach($array as $k => $v)
            {
                end($array);
                
                $part = ($callback !== null ? $callback($k, $v, $k === key($array), $extra, $glue) : $v);
                $ret .= $part ? $part . ($k !== key($array) ? $glue : "") : "";
            }
            return $ret;
        }
    }
    
    private static function select($args=array(), $table_fields, $db)
    {
        if(sizeof($args) < 1)
            throw new Exception("QueryObject::select bad arguments!");
            
        $table = isset($args["table"]) ? $args["table"] : null;
        if($table === null)
            throw new Exception("QueryObject::select bad table name!");
        
        $filter = isset($args["filter"]) ? $args["filter"] : array();
        $groupby = isset($args["groupby"]) ? $args["groupby"] : array();
        $orderby = isset($args["orderby"]) ? $args["orderby"] : array();
        $order = isset($args["order"]) ? $args["order"] : null;
        $limit = isset($args["limit"]) ? $args["limit"] : null;
        $offset = isset($args["offset"]) ? $args["offset"] : null;
        $expr = isset($args["expr"]) ? $args["expr"] : "*";
        $modifier = isset($args["modifier"]) ? $args["modifier"] : null;
        $join = isset($args["join"]) ? $args["join"] : null;
    
        $select_modifier = array("ALL", "DISTINCT", "DISTINCTROW");
        
        return sprintf(self::SELECT, 
            in_array($modifier, $select_modifier) ? $modifier : "",
            self::smart_implode($expr),
            self::smart_implode($table),
            self::sql_where($filter, $table_fields, $db),
            self::sql_groupby($groupby),
            self::sql_orderby($orderby, $order),
            self::sql_limit($limit, $offset),
            $join
        );
    }
    
    public static function where_implode($k, $v, $last, $db, &$glue)
    {
        if(is_array($v) && $k == "__oneof")
        {
            return self::smart_implode($v, " OR ", 
                function ($k2, $v2, $last2, $db2, &$glue2) {
                    return QueryObject::where_implode($k2, $v2, $last2, $db2, $glue2);
                }
            , $db);
        }
        $val = is_array($v) ? $db->mysql_escape_string($v["value"]) : $db->mysql_escape_string($v);
        $tag = is_array($v) ? $v["tag"] : "=";
        return sprintf("%s %s '%s'", $db->mysql_escape_string($k), $tag, $val);
    }
    
    private static function sql_where($filter, $fields, $db)
    {
        $glue = " AND ";
        return sizeof($filter) > 0 ? 
            "WHERE " . self::smart_implode($filter, " AND ", 
                function ($k, $v, $last, $db, &$glue) {
                    return QueryObject::where_implode($k, $v, $last, $db, $glue);
                }
            , $db) :
            "";
    }
    private static function sql_groupby($fields)
    {
        return sizeof($fields) > 0 ? "GROUP BY " . self::smart_implode($fields, ", ") : "";
    }
    private static function sql_orderby($fields, $order="ASC")
    {
        return sizeof($fields) > 0 ? "ORDER BY " . self::smart_implode($fields, ", ") . " " . $order : "";
    }
    private static function sql_limit($limit, $offset)
    {
        return $limit !== null ? sprintf('LIMIT %1$s%2$d', $offset !== null ? $offset . ", " : "", $limit) : "";
    }
    
    public function all()
    {
        $this->query = array("table" => $this->table_name);
        return $this;
    }
    
    public function filter(array $filter)
    {
        $this->query = array("table" => $this->table_name, "filter" => $filter);
        return $this;
    }
    
    public function orderby(array $order)
    {
        $this->query["orderby"] = $order;
        return $this;
    }
    public function reverse()
    {
        $this->query["order"] = isset($this->query["order"]) && $this->query["order"] == "DESC" ? "ASC" : "DESC";
        return $this;
    }
    
    public function select_related($modelClass, $field1, $field2, $expr)
    {
        $this->query["expr"] = $expr;
        $this->query["join"] = sprintf(" AS t1 LEFT OUTER JOIN %s AS t2 ON (t1.%s = t2.%s)", Models::table_name($modelClass), $field1, $field2);
        return $this;
    }
    
    public function get($limit=null, $offset=null, $only_query=false)
    {
        $this->query["limit"] = $limit;
        $this->query["offset"] = $offset;
        $query = self::select($this->query, $this->table_fields, $this->DB);
        return $only_query ? $query : $this->SelectRequest($query, $limit == 1);
    }
    
    private function fields()
    {
        return implode(", ", $this->table_fields);
    }

    public static function NotEqual($value)
    {
        return array("tag" => "!=", "value" => $value);
    }
    public static function LessThan($value)
    {
        return array("tag" => "<", "value" => $value);
    }
    public static function MoreThan($value)
    {
        return array("tag" => ">", "value" => $value);
    }
    
    public static function OneOf($arr)
    {
        return array("__oneof" => $arr);
    }
    
    private function SelectRequest($query, $single=false)
    {
        if(defined("MIGRATE"))
        {
            $migrations = array();
        }
        if(($result = $this->DB->mysql_query($query)) !== FALSE)
        {
            $ret = null;
            while (($row = $result->fetch_assoc()) !== NULL)
            {
                $new_object = new $this->class_name;
                $cname = $this->class_name;
                foreach($cname::ModelFields() as $k => $v)
                {
                    if(is_object($v) && get_parent_class($v) == "Field")
                    {
                        if(!array_key_exists($k, $row))
                        {
                            if(defined("MIGRATE"))
                            {
                                $migrations[] = $v->Migrate($this->table_name);
                            }
                            else
                            {
                                throw new Exception(sprintf("Model %s requests a migration(%s)!", $this->class_name, $k));
                            }
                        }
                        $new_object->$k = isset($row[$k])?$row[$k]:null;
                    }
                }
                
                if(defined("MIGRATE"))
                {
                    if(sizeof($migrations) > 0)
                    {
                        $migrations = self::smart_implode($migrations, $glue="; <br>");
                        throw new Exception($migrations);
                    }
                }
                if($single)
                {
                    $ret = $new_object;
                }
                else
                {
                    if(!$ret)
                        $ret = array();
                    $ret[] = $new_object;
                }
            }
            $result->close();
            return $ret;
        }
        return null;
    }
}
