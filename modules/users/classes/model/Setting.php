<?php
namespace users\model;

class Setting extends \Model
{
    public $id;
    public $name;
    public $title;

    function getEditForm($value=null)
    {
        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("name", "setting[".$this->id."]");
        $tpl->assign("value", $value);
        return $tpl->fetch("elements/default");
    }

    /**
     * Get object by name
     * @param $name
     * @return \users\model\Setting
     */
    public static function getObjectByName($name)
    {
        $class = "\\users\\model\\settings\\".ucfirst($name);
        \AppRoot::debug($class);
        if (class_exists($class))
            return new $class();

        return new \users\model\Setting();
    }

    /**
     * Find by id
     * @param $id
     * @param null $class
     * @return \users\model\Setting
     */
    public static function findById($id, $class = null)
    {
        if ($result = \MySQL::getDB()->getRow("select * from users_setting where id = ?", [$id]))
        {
            $class = self::getObjectByName($result["name"]);
            $entity = new $class();
            $entity->load($result);
            return $entity;
        }

        return null;
    }

    /**
     * Find all
     * @param array $conditions
     * @param array $orderby
     * @param null $class
     * @return \users\model\Setting[]
     */
    public static function findAll($conditions = array(), $orderby = array(), $class = null)
    {
        $where = ["active > 0"];
        $params = array();
        foreach ($conditions as $var => $val) {
            $where[] = $var." = ?";
            $params[] = $val;
        }

        $entities = array();
        if ($results = \MySQL::getDB()->getRows("select * from users_setting
                                                ".((count($where)>0)?"where ".implode(" AND ", $where):"")."
                                                ".((count($orderby)>0)?"order by ".implode(",", $orderby):"")
                                            , $params))
        {
            foreach ($results as $result)
            {
                $class = self::getObjectByName($result["name"]);
                $entity = new $class();
                $entity->load($result);
                $entities[] = $entity;
            }
        }
        return $entities;
    }
}