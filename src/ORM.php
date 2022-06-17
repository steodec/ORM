<?php
/*
 * « Copyright © 2021, Steodec
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (AbstractEntity $entitythe “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * The Software is provided “as is”, without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement. In no event shall the authors or copyright holders X be liable for any claim, damages or other liability, whether in an action of contract, tort or otherwise, arising from, out of or in connection with the software or the use or other dealings in the Software.
 *
 * Except as contained in this notice, the name of the <copyright holders> shall not be used in advertising or otherwise to promote the sale, use or other dealings in this Software without prior written authorization from the Steodec. »
 */

namespace Steodec\ORM;

use PDO;
use PDOException;

/**
 * Gestion de la base de données avec un crud classique
 *
 * @property PDO $_DB     instance de la base de données
 */
class ORM {

    private PDO $_DB;

    public function __construct() {
        $dns = $_ENV['HOST_URL'];
        try {
            $this->_DB = new PDO($dns, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], [PDO::ATTR_PERSISTENT => TRUE]);
        } catch (PDOException $e) {
            var_dump($_ENV['HOST_URL'] . ' | Connection échouée: ' . $e->getMessage());
        }
    }

    /**
     * @return PDO
     */
    public function getDB(): PDO {
        return $this->_DB;
    }

    /**
     * Créer une entitée
     *
     * @param AbstractEntity $entity
     *
     * @return AbstractEntity
     * @throws ORMException
     */
    public function create(AbstractEntity $entity): AbstractEntity {
        $array              = get_object_vars($entity);
        $query_string       = sprintf('INSERT INTO %s', $entity::TABLE_NAME);
        $query_string_field = '( ';
        $query_string_value = 'VALUES (';
        foreach ($entity as $key => $value):
            $class = $entity::class;
            if (!property_exists(new $class, $key)):
                unset($entity->${$key});
            endif;
        endforeach;
        foreach ($entity as $key => $value):
            if (array_key_last((array)$array) != $key):
                $query_string_field .= $key . ', ';
                $query_string_value .= ":$key, ";
            else:
                $query_string_field .= $key . ') ';
                $query_string_value .= ":$key) ";
            endif;
        endforeach;
        $query_string .= $query_string_field . $query_string_value;
        $query        = $this->_DB->prepare($query_string);
        foreach ($array as $key => &$value):
            $query->bindParam(":$key", $value, self::getPDOType($value));
        endforeach;
        if ($query->execute()):
            $entity->setID($this->_DB->lastInsertId($entity::TABLE_NAME));
            return $this->readByID($entity);
        else: return throw new ORMException();
        endif;
    }

    /**
     * @param $type
     *
     * @return int
     */
    private static function getPDOType($type) {
        return match ($type) {
            'double', 'integer' => PDO::PARAM_INT,
            'boolean' => PDO::PARAM_BOOL,
            default => PDO::PARAM_STR,
        };
    }

    /**
     * Retourne l'entité demander filtrant par son ID
     *
     * @param AbstractEntity $entity
     *
     * @return AbstractEntity
     * @throws ORMException
     */
    public function readByID(AbstractEntity $entity): AbstractEntity {
        $query_string = sprintf("SELECT * FROM %s WHERE id = %d", $entity::TABLE_NAME, $entity->getID());
        $query        = $this->_DB->query($query_string);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_CLASS, $entity::class);
        return (count($result) > 0) ? $result[0] : throw new ORMException();
    }

    /**
     * Retourne l'entité demander filtrant par un champ
     *
     * @param AbstractEntity $entity
     * @param string $column
     * @param mixed $value
     *
     * @return AbstractEntity[]
     * @throws ORMException
     */
    public function readByColumn(AbstractEntity $entity, string $column, mixed $value): iterable {
        $query_string = sprintf("SELECT * FROM %s WHERE %s = '%s'", $entity::TABLE_NAME, $column, $value);
        $query        = $this->_DB->query($query_string);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_CLASS, $entity::class);
        return (count($result) > 0) ? $result : throw new ORMException();
    }

    /**
     * Retourne l'entité demander filtrant par un champ
     *
     * @param AbstractEntity $entity
     * @param array $params
     *
     * @return AbstractEntity[]
     * @throws ORMException
     */
    public function readByColumnMultiple(AbstractEntity $entity, array $params): iterable {
        $query_string = sprintf("SELECT * FROM %s WHERE ", $entity::TABLE_NAME);
        foreach ($params as $column => $value):
            if ($column === array_key_last($params)):
                $query_string .= $column . " = '" . $value . "';";
            else:
                $query_string .= $column . " = '" . $value . "' AND ";
            endif;
        endforeach;
        $query = $this->_DB->query($query_string);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_CLASS, $entity::class);
        return (count($result) > 0) ? $result : throw new ORMException("Une Erreur c'est produite");
    }

    /**
     * Mets à jour l'entité demander
     *
     * @param AbstractEntity $entity
     *
     * @return bool
     */
    public function update(AbstractEntity $entity): bool {
        $array              = get_object_vars($entity);
        $query_string       = sprintf('UPDATE %s', $entity::TABLE_NAME);
        $query_string_field = ' SET ';
        foreach ($entity as $key => $value):
            $class = $entity::class;
            if (!property_exists(new $class, $key)):
                unset($entity->${$key});
            endif;
        endforeach;
        foreach ($entity as $key => $value):
            if (array_key_last($array) != $key):
                $query_string_field .= $key . ' = :' . $key . ', ';
            else:
                $query_string_field .= $key . ' = :' . $key . ' ';
            endif;
        endforeach;
        $query_string .= $query_string_field;
        $query_string .= sprintf('WHERE id = %d', $entity->getID());
        $query        = $this->_DB->prepare($query_string);
        foreach ($array as $key => &$value):
            $query->bindParam(":$key", $value, self::getPDOType($value));
        endforeach;
        return ($query->execute());
    }

    /**
     * @throws ORMException
     */
    private function lastID(AbstractEntity $entity): int {
        $entities = $this->read($entity);
        usort($entities, function (AbstractEntity $a, AbstractEntity $b) {
            return ($a->getID() < $b->getID()) ? -1 : 1;
        });
        return $entities[count($entities) - 1]->getID();
    }

    /**
     * Retourne le tableaux de toutes les entités demander
     *
     * @param AbstractEntity $entity
     * @param int|null $LIMIT
     *
     * @return AbstractEntity[]
     * @throws ORMException
     */
    public function read(AbstractEntity $entity, ?int $LIMIT = NULL): iterable {
        if (is_null($LIMIT)):
            $LIMIT_string = ";";
        else:
            $LIMIT_string = "LIMIT " . $LIMIT;
        endif;
        $query_string = sprintf('SELECT * FROM %s %s', $entity::TABLE_NAME, $LIMIT_string);
        $query        = $this->_DB->query($query_string);
        $result       = $query->execute();
        return ($result) ? $query->fetchAll(PDO::FETCH_CLASS, $entity::class) : throw new ORMException();
    }

    /**
     * Supprime l'entité filtrer par son ID
     *
     * @param
     *
     * @return bool
     */
    public function delete(AbstractEntity $entity): bool {
        $query_string = sprintf("DELETE FROM %s WHERE id = %d", $entity::TABLE_NAME, $entity->getID());
        $query        = $this->_DB->query($query_string);
        return $query->execute();
    }

}