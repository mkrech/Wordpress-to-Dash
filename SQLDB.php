<?php
/**
 * Aura SQL Wrapper
 *
 * @author mkrech
 * @license http://opensource.org/licenses/MIT MIT License
 */

require_once("vendor/autoload.php");

use Aura\SqlQuery\QueryFactory;

/**
 * Created by PhpStorm.
 * User: morbo
 * Date: 29.09.15
 * Time: 16:35
 */
class SQLDB
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @param $pdo
     * @param string $dbtype
     */
    public function __construct($pdo, $dbtype = 'sqlite')
    {
        $this->queryFactory = new QueryFactory($dbtype, QueryFactory::COMMON);
        $this->pdo = $pdo;
    }

    /**
     * close
     */
    public function close()
    {
        $this->pdo = null;
    }

    /**
     * @param $table
     * @param array $params
     * @param string $where
     * @param bool|false $debug
     * @return array
     */
    public function select($table, $params = array(), $where = '', $debug = false)
    {
        $result = [];
        $select = $this->queryFactory->newSelect();
        $select->cols($params)
            ->fromRaw($table)
            ->where($where);

        $this->showStatement($select, $debug);
        $sth = $this->execute($select);
        while ($res = $sth->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $res;
        }

        return $result;
    }

    /**
     * @param $table
     * @param bool|false $debug
     * @return mixed
     */
    public function truncate($table, $debug = false)
    {
        $delete = $this->queryFactory->newDelete();
        $delete->from($table);
        $this->showStatement($delete, $debug);
        return $this->execute($delete);
    }

    /**
     * @param $table
     * @param array $params
     * @param bool|false $debug
     * @return mixed
     */
    public function insert($table, $params = array(), $debug = false)
    {
        $insert = $this->queryFactory->newInsert();
        $insert->into($table)
            ->cols($params);
        $this->showStatement($insert, $debug);
        return $this->execute($insert);
    }


    /**
     * @param $instance
     * @return mixed
     */
    private function execute($instance)
    {
        $sth = $this->pdo->prepare($instance->getStatement());
        $sth->execute($instance->getBindValues());
        return $sth;
    }

    /**
     * @param $stmt
     * @param bool|false $debug
     */
    private function showStatement($stmt, $debug = false)
    {
        if ($debug) {
            echo $stmt->getStatement();
        }
    }

}