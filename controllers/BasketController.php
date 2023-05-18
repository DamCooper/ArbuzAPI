<?php

class BasketController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function addToBasket()
    {
        $subscriptionId = $_POST['subscription_id'];
        $productIds = explode(',', $_POST['product_ids']);
        $quantities = explode(',', $_POST['quantities']);

        $subscription = $this->getSubscription($subscriptionId);

        if (!$subscription) {
            $this->sendResponse(['error' => 'Subscription not found'], 404);
        }

        foreach ($productIds as $index => $productId) {
            $quantity = $quantities[$index];
            $basketItem = $this->getBasketItem($subscriptionId, $productId);

            if ($basketItem) {
                $updatedQuantity = $basketItem['quantity'] + $quantity;
                $this->updateBasketItem($subscriptionId, $productId, $updatedQuantity);
            } else {
                $this->insertBasketItem($subscriptionId, $productId, $quantity);
            }
        }

        $responseData = [
            'message' => 'Products added to basket successfully',
            'subscription_id' => $subscriptionId,
            'product_ids' => $productIds,
            'quantities' => $quantities,
        ];
        $this->sendResponse($responseData, 200);
    }

    public function getBasketInfo()
    {
        $subscriptionId = $_GET['subscription_id'];

        $subscription = $this->getSubscription($subscriptionId);

        if (!$subscription) {
            $this->sendResponse(['error' => 'Subscription not found'], 404);
        }

        $unavailableProducts = [];
        $basketItems = $this->getBasketItems($subscriptionId);
        $products = [];
        $totalWeight = 0;

        foreach ($basketItems as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];
            $product = $this->getProduct($productId);

            if ($product) {
                if ($product['availability'] > 0) {
                    $product['quantity'] = $quantity;
                    $products[] = $product;
                    $totalWeight += $product['weight'] * $quantity;
                } else {
                    $unavailableProducts[] = $product;
                }
            }
        }

        $totalWeight = number_format($totalWeight, 2);
        $responseData = [
            'subscription' => $subscription,
            'total_weight' => $totalWeight,
            'products' => $products,
            'unavailable_products' => $unavailableProducts,
        ];

        $this->sendResponse($responseData, 200);
    }

    public function deleteBasketItemsBySubscriptionId()
    {
        $subscriptionId = $_GET['subscription_id'];
        $rowCount = $this->deleteBasketItems($subscriptionId);

        if ($rowCount > 0) {
            $responseData = [
                'message' => 'Basket items deleted successfully',
                'subscription_id' => $subscriptionId
            ];
            $this->sendResponse($responseData, 200);
        } else {
            $responseData = [
                'message' => 'No basket items found for the given subscription',
                'subscription_id' => $subscriptionId
            ];
            $this->sendResponse($responseData, 404);
        }
    }

    private function sendResponse($data, $statusCode)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }

    private function getSubscription($subscriptionId)
    {
        $subscriptionQuery = $this->db->prepare("SELECT * FROM Subscriptions WHERE id = :id");
        $subscriptionQuery->execute(['id' => $subscriptionId]);
        return $subscriptionQuery->fetch(PDO::FETCH_ASSOC);
    }

    private function getBasketItem($subscriptionId, $productId)
    {
        $basketQuery = $this->db->prepare("SELECT * FROM basket WHERE subscription_id = :subscription_id AND product_id = :product_id");
        $basketQuery->execute(['subscription_id' => $subscriptionId, 'product_id' => $productId]);
        return $basketQuery->fetch(PDO::FETCH_ASSOC);
    }

    private function updateBasketItem($subscriptionId, $productId, $quantity)
    {
        $updateQuery = $this->db->prepare("UPDATE basket SET quantity = :quantity WHERE subscription_id = :subscription_id AND product_id = :product_id");
        $updateQuery->execute(['quantity' => $quantity, 'subscription_id' => $subscriptionId, 'product_id' => $productId]);
    }

    private function insertBasketItem($subscriptionId, $productId, $quantity)
    {
        $basketData = [
            'subscription_id' => $subscriptionId,
            'product_id' => $productId,
            'quantity' => $quantity,
        ];
        $insertQuery = $this->db->prepare("INSERT INTO basket (subscription_id, product_id, quantity) VALUES (:subscription_id, :product_id, :quantity)");
        $insertQuery->execute($basketData);
    }

    private function getBasketItems($subscriptionId)
    {
        $basketQuery = $this->db->prepare("SELECT * FROM basket WHERE subscription_id = :subscription_id");
        $basketQuery->execute(['subscription_id' => $subscriptionId]);
        return $basketQuery->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getProduct($productId)
    {
        $productQuery = $this->db->prepare("SELECT * FROM Products WHERE id = :id");
        $productQuery->execute(['id' => $productId]);
        return $productQuery->fetch(PDO::FETCH_ASSOC);
    }

    private function deleteBasketItems($subscriptionId)
    {
        $deleteQuery = $this->db->prepare("DELETE FROM basket WHERE subscription_id = :subscription_id");
        $deleteQuery->execute(['subscription_id' => $subscriptionId]);
        return $deleteQuery->rowCount();
    }
}

?>