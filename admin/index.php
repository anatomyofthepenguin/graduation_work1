<?php

require '../src/functions.php';

$users = getUsers();
$orders = getOrders();

echo renderPage('admin.twig', ["users" => $users, "orders" => $orders]);
