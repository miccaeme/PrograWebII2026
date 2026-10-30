<?php

class MyDatabase
{
    private $conexion;

    public function __construct($host, $user, $pass, $db)
    {
        $this->conexion = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    }

    public function queryOne(string $sql)
    {
        Log::info($sql);
        $resultado = $this->conexion->query($sql, PDO::FETCH_ASSOC);
        $fila = $resultado->fetch();
        return $fila === false ? null : $fila;
    }

    public function queryAll(string $sql)
    {
        Log::info($sql);
        $resultado = $this->conexion->query($sql, PDO::FETCH_ASSOC);
        return $resultado->fetchAll();
    }

    public function execute(string $sql)
    {
        Log::info($sql);
        return $this->conexion->exec($sql);
    }

    public function lastInsertId()
    {
        return $this->conexion->lastInsertId();
    }

    public function queryOneParams(string $sql, array $params)
    {
        Log::info($sql);
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($params);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    public function beginTransaction()
    {
        return $this->conexion->beginTransaction();
    }

    public function commit()
    {
        return $this->conexion->commit();
    }

    public function rollback()
    {
        return $this->conexion->rollBack();
    }

    public function executeParams(string $sql, array $params)
    {
        Log::info($sql);
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function queryParams(string $sql, array $params)
    {
        Log::info($sql);
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
