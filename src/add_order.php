<?php

require './functions.php';

$orderInfo = addOrder($_POST);
if ($orderInfo["error"]) {
    header("Location:/index.php?error=" . $orderInfo["error"]);
}

$mailResult = sendMail($orderInfo, $_POST);
if ($mailResult["error"]) {
    header("Location:/index.php?error=" . $mailResult["error"]);
} else {
    header("Location:/");
}
