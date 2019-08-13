<?php

require 'db.php';
require 'errors.php';
require __DIR__ . '/../vendor/autoload.php';

function addOrder($orderData)
{
    global $dbConnect;

    $name = $orderData["name"];
    $phone = $orderData["phone"];
    $email = $orderData["email"];
    $comment = $orderData["comment"] ?? '';
    $payment = $orderData["payment"] ? 1 : 0;
    $callback = $orderData["callback"] ? 1 : 0;

    if (!$name) {
        return ["error" => ERROR_EMPTY_NAME];
    }
    if (!$email) {
        return ["error" => ERROR_EMPTY_EMAIL];
    }
    if (!$phone) {
        return ["error" => ERROR_EMPTY_PHONE];
    }

    $address = createAddress($orderData["street"], $orderData["home"], $orderData["part"], $orderData["appt"], $orderData["floor"]);

    if (!$address) {
        return ["error" => ERROR_EMPTY_ADDRESS];
    }

    $queryString = "SELECT id FROM users WHERE email = ?";
    $getUserQuery = $dbConnect->prepare($queryString);
    $getUserQuery->execute([$email]);
    $userId = $getUserQuery->fetchColumn();

    if (!$userId) {
        $queryString = "INSERT INTO users SET email = ?, name = ?, phone = ?";
        $insertUserQuery = $dbConnect->prepare($queryString);
        $insertResult = $insertUserQuery->execute([$email, $name, $phone]);

        if (!$insertResult) {
            return ["error" => ERROR_DB_QUERY];
        }

        sendMailSwift($email);

        $userId = $dbConnect->lastInsertId();
    }

    $queryString = "INSERT INTO orders SET user_id = ?, address = ?, comment = ?, payment = ?, callback = ?";
    $insertData = [$userId, $address, $comment, $payment, $callback];
    $insertOrderQuery = $dbConnect->prepare($queryString);
    $insertResult = $insertOrderQuery->execute($insertData);
    if (!$insertResult) {
        return ["error" => ERROR_DB_QUERY];
    }
    $orderID = $dbConnect->lastInsertId();

    return ["orderID" => $orderID, "userID" => $userId];
}

function getUsers()
{
    global $dbConnect;

    $queryString = "SELECT * FROM users";
    $getUsersQuery = $dbConnect->prepare($queryString);
    $getUsersQuery->execute();

    if (!$getUsersQuery) {
        return ["error" => ERROR_DB_QUERY];
    }

    return $getUsersQuery->fetchAll();
}

function getOrders()
{
    global $dbConnect;

    $queryString = "SELECT * FROM orders";
    $getOrdersQuery = $dbConnect->prepare($queryString);
    $getOrdersQuery->execute();

    if (!$getOrdersQuery) {
        return ["error" => ERROR_DB_QUERY];
    }

    return $getOrdersQuery->fetchAll();
}

function sendMail($orderInfo, $data)
{
    global $dbConnect;
    $email = $data["email"];
    $address = createAddress($data["street"], $data["home"], $data["part"], $data["appt"], $data["floor"]);

    $getOrdersQuery = "SELECT id FROM orders WHERE user_id = ?";
    $getOrdersCount = $dbConnect->prepare($getOrdersQuery);
    $getOrdersCount->execute([$orderInfo["userID"]]);
    if (!$getOrdersCount) {
        return ["error" => ERROR_DB_QUERY];
    }

    $ordersCount = $getOrdersCount->rowCount();
    if ($ordersCount > 1) {
        $endMessage = "Спасибо! Это уже $ordersCount заказ";
    } else {
        $endMessage = "Спасибо - это ваш первый заказ";
    }

    $subject = "Информация по заказу - " . $orderInfo["orderID"];
    $message = "Заказ - {$orderInfo['orderID']} " . PHP_EOL;
    $message .= "Ваш заказ будет доставлен по адресу $address" . PHP_EOL;
    $message .= "Содержимое заказа: DarkBeefBurger за 500 рублей, 1 шт" . PHP_EOL;
    $message .= $endMessage;

    $headers = 'Content-type: text/plain; charset="utf-8"';
    $mailSend = mail($email, $subject, $message, $headers);

    if (!$mailSend) {
        return ["error" => ERROR_MAIL_SEND];
    }

    return true;
}

function sendMailSwift(string $recipientEmail)
{
    $mailConfig = require __DIR__ . '/mail_config.php';

    $transport = (new Swift_SmtpTransport($mailConfig["smtp"], $mailConfig["port"], $mailConfig['encryption']))
        ->setUsername($mailConfig["userName"])
        ->setPassword($mailConfig["password"])
    ;
    $message = (new Swift_Message('Wonderful Subject'))
        ->setFrom(['admin99@burgers.ru'])
        ->setTo([$recipientEmail])
        ->setBody('Вы успешно зарегистрировались на сайте!')
    ;
    $mailer = new Swift_Mailer($transport);

    $result = $mailer->send($message);

    if (!$result) {
        return ["error" => ERROR_MAIL_SEND];
    }

    return true;
}

function renderPage(string $template, array $data = null)
{
    $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates/');
    $twig = new \Twig\Environment($loader);

    if ($data) {
        return $twig->render($template, $data);
    }

    return $twig->render($template);
}

function createAddress($street, $home, $part, $appt, $floor)
{
    $address = "$street, $home, $part, $appt, $floor";
    $address = trim($address, " ,");

    return $address;
}
