<?php

namespace App\Db;

use App\Tools\DbConnect;
use Exception;
use PDO;

class TransactionManager
{
    private PDO $pdo;
    private bool $transactionOwned  = false;

    public function __construct(DbConnect $dbConnect)
    {
        $this->pdo = $dbConnect->getConnection();
    }

    /**
     * @param callable(callable $rollBack):mixed $callback
     */
    public function withTransaction(callable $callback)
    {
        if ($this->pdo->inTransaction() || $this->transactionOwned)
            throw new Exception('Предыдущая транзакция не завершена!');

        $rolledBackInside = false;
        $rollBack = function () use (&$rolledBackInside) {
            $rolledBackInside = true;
            $this->transactionOwned = false;
            $this->rollBackTransaction();
        };

        try {
            $this->beginTransaction();
            $this->transactionOwned  = true;

            $res = $callback($rollBack);
            if ($rolledBackInside) return $res;

            $this->transactionOwned  = false;
            $this->submitTransaction();

            return $res;
        } catch (\Throwable $e) {
            $this->transactionOwned  = false;
            $this->rollBackTransaction();
            throw $e;
        }
    }

    public function beginTransaction()
    {
        if ($this->transactionOwned) return;
        if ($this->pdo->inTransaction()) return;
        $this->pdo->beginTransaction();
    }

    public function submitTransaction()
    {
        if ($this->transactionOwned) return;
        if ($this->pdo->inTransaction())
            $this->pdo->commit();
    }

    public function rollBackTransaction()
    {
        if ($this->transactionOwned) return;
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
