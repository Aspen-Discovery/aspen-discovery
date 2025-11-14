<?php 

function checkDatabaseConnection(PDO $pdo): bool {

    if ($pdo === null) {
        DockerLogger::error("Database connection is null");
        return false;
    }
    
    try {
        $statement = 'SELECT 1;';
        $stmt = $pdo->prepare($statement);
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function isDatabaseInitialized(PDO $pdo): bool {
    try {
        $statement = 'SELECT libraryId FROM library LIMIT 1;';
        $stmt = $pdo->prepare($statement);
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        return false;
    }
}