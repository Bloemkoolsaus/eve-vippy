<?php
class Model
{
    protected $_dbProperties = null;
    protected $_table = null;
    protected $_keyfield = "id";

    function __construct($id=false)
    {
        if ($id) {
            $this->id = $id;
            $this->load();
        }
    }

    function load($result=false)
    {
        if (!$result)
        {
            $where = array();
            $params = array();
            foreach ($this->getDBKeyFields() as $field) {
                $where[] = $field." = ?";
                $params[] = $this->$field;
            }

            $result = \MySQL::getDB()->getRow("SELECT * FROM ".$this->getDBTable()." WHERE ".implode(" AND ", $where), $params);
        }

        if ($result)
        {
            foreach ($this->getDBProperties() as $property => $field) {
                $this->$property = $result[$field];
            }
        }
    }

    function store()
    {
        $data = array();
        $where = array();
        foreach ($this->getDBProperties() as $property => $field) {
            $data[$field] = $this->$property;
        }
        foreach ($this->getDBKeyFields() as $field) {
            $where[$field] = $data[$field];
        }

        $result = \MySQL::getDB()->updateinsert($this->getDBTable(), $data, $where);
        if (is_numeric($result) && count($this->getDBKeyFields()) == 1) {
            foreach ($this->getDBKeyFields() as $field) {
                $this->$field = $result;
            }
        }
    }

    function delete()
    {
        $data = array();
        $where = array();
        foreach ($this->getDBProperties() as $property => $field) {
            $data[$field] = $this->$property;
        }
        foreach ($this->getDBKeyFields() as $field) {
            $where[$field] = $data[$field];
        }
        \MySQL::getDB()->delete($this->getDBTable(),$where);
    }

    /**
     * Get database table
     * @return string
     */
    private function getDBTable()
    {
        if ($this->_table == null)
            $this->_table = self::getDBTableByClass();

        return $this->_table;
    }

    /**
     * Get database key fields
     * @return string[]
     */
    private function getDBKeyFields()
    {
        $keyfields = array();

        if (is_array($this->_keyfield)) {
            foreach ($this->_keyfield as $field) {
                $keyfields[] = $field;
            }
        } else
            $keyfields[] = $this->_keyfield;

        return $keyfields;
    }

    /**
     * Get properties to store/load from database
     *  - Properties die beginnen met een underscore (_) worden niet in de databse gezet.
     *  - Docblock @dbfield om db kolom te specifieren. null = niet in database.
     * @return string[]
     */
    protected function getDBProperties()
    {
        if ($this->_dbProperties === null)
        {
            $this->_dbProperties = array();
            $class = new \ReflectionClass(get_called_class());
            foreach ($class->getProperties() as $property)
            {
                $name = $property->getName();
                if ($name[0] == "_")
                    continue;

                /** Database veld niet te herleiden uit property naam */
                $dbField = strtolower($property->getName());
                if (preg_match('/\\s@dbField(\\s[\\w\\\\]+)?\\s/', $property->getDocComment(), $parameters)) {
                    $dbField = trim($parameters[1]);
                    if ($dbField == "null")
                        continue;
                }

                $this->_dbProperties[$property->getName()] = $dbField;
            }
        }
        return $this->_dbProperties;
    }


    /**
     * Get database table
     * @param null $class
     * @return string
     */
    private static function getDBTableByClass($class=null)
    {
        if ($class == null)
            $class = get_called_class();

        $parts = array();
        foreach (explode("\\", $class) as $i => $part) {
            if ($i != 1)
                $parts[] = strtolower(trim($part));
        }
        return implode("_", $parts);
    }

    public static function findById($id, $class=null)
    {
        if ($result = \MySQL::getDB()->getRow("SELECT * FROM ".self::getDBTableByClass($class)." WHERE id = ?", array($id)))
        {
            if ($class == null)
                $class = get_called_class();

            $entity = new $class();
            $entity->load($result);
            return $entity;
        }

        return null;
    }

    /**
     * Find all instances
     * @param array $conditions
     * @param array $orderby
     * @param null  $class
     * @return static[]
     */
    public static function findAll($conditions=array(), $orderby=array(), $class=null)
    {
        if ($class == null)
            $class = get_called_class();

        $where = array();
        $params = array();
        foreach ($conditions as $var => $val) {
            $where[] = $var." = ?";
            $params[] = $val;
        }

        $entities = array();
        if ($results = \MySQL::getDB()->getRows("SELECT *
                                                FROM    ".self::getDBTableByClass($class)."
                                                ".((count($where)>0)?"WHERE ".implode(" AND ", $where):"")."
                                                ".((count($orderby)>0)?"ORDER BY ".implode(",", $orderby):"")
                                    , $params))
        {
            foreach ($results as $result)
            {
                $entity = new $class();
                $entity->load($result);
                $entities[] = $entity;
            }
        }
        return $entities;
    }
}