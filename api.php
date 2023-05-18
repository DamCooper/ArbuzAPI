<?php
require_once 'controllers/SubscriptionController.php';
require_once 'controllers/ProductController.php';
require_once 'controllers/BasketController.php';

$host = 'localhost'; 
$dbName = 'arbuz_kz'; 
$username = 'root'; 
$password = ''; 

$db = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $username, $password);

$subscriptionController = new SubscriptionController($db);
$productController = new ProductController($db);
$basketController = new BasketController($db);

$baseURI = '/arbuzapi/api.php/';

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestURI = str_replace($baseURI, '', $_SERVER['REQUEST_URI']);

$routes = [
    'POST' => [
        'subscriptions' => 'createSubscription',
        'products' => 'createProduct',
        'basket' => 'addToBasket'
    ],
    'GET' => [
        'subscriptions/\?id=(\d+)' => 'getSubscription',
        'products/\?id=(\d+)' => 'getProduct',
        'basket/\?subscription_id=(\d+)' => 'getBasketInfo'
    ],
    'PUT' => [
        'subscriptions/\?id=(\d+)' => 'updateSubscription'
    ],
    'DELETE' => [
        'basket/remove/\?subscription_id=(\d+)' => 'deleteBasketItemsBySubscriptionId'
    ]
];

if (isset($routes[$requestMethod])) {
    foreach ($routes[$requestMethod] as $pattern => $handler) {
        if (preg_match('#' . $pattern . '#', $requestURI, $matches)) {
            $controller = getControllerInstance($handler);
            $method = getControllerMethod($handler);
            $controller->$method();
            break;
        }
    }
}

function getControllerInstance($handler)
{
    global $subscriptionController, $productController, $basketController;

    switch ($handler) {
        case 'createSubscription':
        case 'getSubscription':
        case 'updateSubscription':
            return $subscriptionController;
        case 'createProduct':
        case 'getProduct':
            return $productController;
        case 'addToBasket':
        case 'getBasketInfo':
        case 'deleteBasketItemsBySubscriptionId':
            return $basketController;
        default:
            return null;
    }
}

function getControllerMethod($handler)
{
    return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $handler))));
}
?>
