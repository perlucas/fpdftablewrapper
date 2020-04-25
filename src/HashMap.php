<?php

/**
 * representa una tabla hash o hashmap: estructura de datos que permite asociar una clave (string) a un dato (mixed)
 */
class HashMap
{
    /**
     * array asociativo que representa el core de esta clase
     *
     * @var array
     */
    protected $map;

    public function __construct()
    {
        $this->map = array();
    }

    /**
     * guarda un valor bajo la clave enviada por parametro
     * 
     * @param string $alias
     * @param mixed $value
     * @return void
     */
    public function set($alias, $value)
    {
        $this->map[$alias] = $value;
    }

    /**
     * recupera un valor almacenado en una clave
     * 
     * @param string $alias
     * @return mixed
     */
    public function get($alias)
    {
        return $this->map[$alias];
    }
    
    /**
     * remueve un valor asignado en una clave
     * 
     * @param string $alias
     * @return void
     */
    public function remove($alias)
    {
        if (array_key_exists($alias, $this->map)) {
            unset($this->map[$alias]);
        }
    }

    /**
     * devuelve true si existe la clave en el hashmap
     * 
     * @param string $alias
     * @return boolean
     */
    public function exists($alias)
    {
        return array_key_exists($alias, $this->map);
    }
    
    /**
     * permite obtener todos los valores de este hashmap
     * 
     * @return array
     */
    public function getAll()
    {
        return array_values($this->map);
    }
    
    /**
     * devuelve todas las claves en uso del map
     * 
     * @return array
     */
    public function getAllKeys()
    {
        return array_keys($this->map);
    }

}
