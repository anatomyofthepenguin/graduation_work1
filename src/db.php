<?php

require 'config.php';

try {
    $dbConnect = new PDO("mysql:host=$host;dbname=$dbName", "$userName", "$password");
} catch (PDOException $exception) {
    echo $exception->getMessage();
    die;
}
