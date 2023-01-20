<?php

namespace Services;

class DatabaseConnector
{
    static function getConnection() : \Doctrine\DBAL\Connection {
        $connectionParams = [
            'url' => 'mysql://' . $_ENV['DB_USER'] . ':' . $_ENV['DB_PASS'] . '@' . $_ENV['DB_HOST'] . '/' . $_ENV['DB_NAME']
        ];

        try {
            $connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
            $connection->connect();
        } catch (\Doctrine\DBAL\Exception $e) {
            if ($_ENV['DEBUG']) {
                echo($e->getMessage() . PHP_EOL);
            } else {
                $filename = __DIR__ . '/../../storage/db.log';
                $file = new \SplFileObject($filename, 'a');
                $file->fwrite(
                    (new \DateTime())->format(\DateTimeInterface::RSS) .
                    ' - ' .
                    $e->getMessage() .
                    PHP_EOL);
                header('Location: /unavailable.html');
            }
            exit();
        }
        return $connection;
    }
}