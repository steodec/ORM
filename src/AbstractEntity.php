<?php
/*
 * « Copyright © 2021, Steodec
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * The Software is provided “as is”, without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement. In no event shall the authors or copyright holders X be liable for any claim, damages or other liability, whether in an action of contract, tort or otherwise, arising from, out of or in connection with the software or the use or other dealings in the Software.
 *
 * Except as contained in this notice, the name of the <copyright holders> shall not be used in advertising or otherwise to promote the sale, use or other dealings in this Software without prior written authorization from the Steodec. »
 */

namespace Steodec\ORM;

use JetBrains\PhpStorm\Pure;
use PDO;

abstract class AbstractEntity
{
    /**
     * Nom de la table en BDD
     */
    const TABLE_NAME = "";
    /**
     * @var ORM
     */
    private ORM $ORM;
    /**
     * Clef primaire dans la base
     *
     * @var int
     */
    public int $id;

    /**
     *
     */
    public function __construct()
    {
        $this->ORM = new ORM();
    }

    /**
     * @return int
     */
    public function getID(): int
    {
        return $this->id;
    }


    /**
     * @param int $id
     *
     * @return void
     */
    public function setID(int $id)
    {
        $this->id = $id;
    }

    /**
     * @throws ORMException
     * @return AbstractEntity
     */
    public function create(): AbstractEntity
    {
        return $this->ORM->create($this);
    }

    /**
     * @throws ORMException
     * @return AbstractEntity[]
     */
    public function read(): iterable
    {
        return $this->ORM->read($this);
    }

    /**
     * @throws ORMException
     * @return AbstractEntity
     */
    public function readByID(): AbstractEntity
    {
        return $this->ORM->readByID($this);
    }

    /**
     * @param string $column
     * @param mixed  $value
     *
     * @throws ORMException
     * @return AbstractEntity[]
     */
    public function readByColumn(string $column, mixed $value): iterable
    {
        return $this->ORM->readByColumn($this, $column, $value);
    }

    /**
     * @param array $params
     *
     * @throws ORMException
     * @return AbstractEntity[]
     */
    public function readByColumnMultiple(array $params): iterable
    {
        return $this->ORM->readByColumnMultiple($this, $params);
    }

    /**
     * @return bool
     */
    public function update(): bool {
        return $this->ORM->update($this);
    }

    /**
     * @return bool
     */
    public function delete(): bool
    {
        return $this->ORM->delete($this);
    }

    /**
     * @return PDO
     */
    #[Pure] protected function getDB(): PDO
    {
        return $this->ORM->getDB();
    }


}