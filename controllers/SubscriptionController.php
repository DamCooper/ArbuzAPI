<?php

class SubscriptionController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function createSubscription()
    {
        $data = $_POST;

        $requiredFields = ['delivery_day', 'customer_name', 'delivery_address', 'phone', 'start_date', 'end_date', 'delivery_period'];
        $missingFields = array_diff($requiredFields, array_keys($data));
        if (!empty($missingFields)) {
            $this->sendErrorResponse('Missing required fields: ' . implode(', ', $missingFields), 400);
        }

        if (!preg_match('/^\+?\d{3}-?\d{3}-?\d{2}-?\d{2}$/', $data['phone'])) {
            $this->sendErrorResponse('Invalid phone number format', 400);
        }

        $startDate = strtotime($data['start_date']);
        $endDate = strtotime($data['end_date']);
        if (!$startDate || !$endDate || $startDate > $endDate) {
            $this->sendErrorResponse('Invalid start or end date', 400);
        }

        $subscription = [
            'customer_name' => $data['customer_name'],
            'delivery_address' => $data['delivery_address'],
            'phone' => $data['phone'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'delivery_period' => $data['delivery_period'],
            'delivery_day' => $data['delivery_day']
        ];
        $query = $this->db->prepare("INSERT INTO Subscriptions (customer_name, delivery_address, phone, start_date, end_date, delivery_period, delivery_day) VALUES (:customer_name, :delivery_address, :phone, :start_date, :end_date, :delivery_period, :delivery_day)");
        $query->execute($subscription);

        $responseData = [
            'message' => 'Subscription created successfully',
            'subscription_id' => $this->db->lastInsertId()
        ];
        $this->sendSuccessResponse($responseData, 200);
    }

    public function getSubscription()
    {
        $subscriptionId = $_GET['id'];

        $query = $this->db->prepare("SELECT * FROM Subscriptions WHERE id = :id");
        $query->execute(['id' => $subscriptionId]);
        $subscription = $query->fetch(PDO::FETCH_ASSOC);

        if (!$subscription) {
            $this->sendErrorResponse('Subscription not found', 404);
        }

        $this->sendSuccessResponse($subscription, 200);
    }

    public function addToBasket($args)
    {
        $data = $_POST;
        $subscriptionId = $args['id'];

        $requiredFields = ['product_name', 'quantity', 'weight'];
        $missingFields = array_diff($requiredFields, array_keys($data));
        if (!empty($missingFields)) {
            $this->sendErrorResponse('Missing required fields: ' . implode(', ', $missingFields), 400);
        }

        if ($data['quantity'] <= 0 || $data['weight'] <= 0) {
            $this->sendErrorResponse('Quantity and weight should be positive values', 400);
        }

        $product = [
            'subscription_id' => $subscriptionId,
            'product_name' => $data['product_name'],
            'quantity' => $data['quantity'],
            'weight' => $data['weight']
        ];
        $query = $this->db->prepare("INSERT INTO Basket (subscription_id, product_name, quantity, weight) VALUES (:subscription_id, :product_name, :quantity, :weight)");
        $query->execute($product);

        $responseData = [
            'message' => 'Product added to basket successfully',
            'product_id' => $this->db->lastInsertId()
        ];
        $this->sendSuccessResponse($responseData, 200);
    }

    public function updateSubscription()
    {
        $subscriptionId = $_GET['id'];

        $subscription = $this->getSubscriptionData($subscriptionId);

        if (!$subscription) {
            $this->sendErrorResponse('Subscription not found', 404);
        }

        $requestData = json_decode(file_get_contents('php://input'), true);

        if (!is_array($requestData)) {
            $this->sendErrorResponse('Invalid request data', 400);
        }

        $updateData = $this->filterUpdateData($requestData);

        if (empty($updateData)) {
            $this->sendErrorResponse('No fields to update', 400);
        }

        $updateData['id'] = $subscriptionId;
        $this->updateSubscriptionData($updateData);

        $responseData = [
            'message' => 'Subscription updated successfully',
            'subscription_id' => $subscriptionId,
            'updated_data' => $requestData,
        ];
        $this->sendSuccessResponse($responseData, 200);
    }

    private function filterUpdateData($requestData)
    {
        $allowedFields = ['customer_name', 'delivery_address', 'delivery_day', 'delivery_period', 'phone', 'start_date', 'end_date'];
        $updateData = array_intersect_key($requestData, array_flip($allowedFields));

        return $updateData;
    }

    private function updateSubscriptionData($updateData)
    {
        $updateQuery = $this->buildUpdateQuery($updateData);
        $updateStatement = $this->db->prepare($updateQuery);
        $updateStatement->execute($updateData);
    }

    private function buildUpdateQuery($updateData)
    {
        $updateQuery = 'UPDATE Subscriptions SET ';

        foreach ($updateData as $key => $value) {
            if ($key !== 'id') {
                $updateQuery .= $key . ' = :' . $key . ', ';
            }
        }

        $updateQuery = rtrim($updateQuery, ', ') . ' WHERE id = :id';

        return $updateQuery;
    }

    private function getSubscriptionData($subscriptionId)
    {
        $query = $this->db->prepare("SELECT * FROM Subscriptions WHERE id = :id");
        $query->execute(['id' => $subscriptionId]);
        return $query->fetch(PDO::FETCH_ASSOC);
    }


    private function sendSuccessResponse($data, $statusCode)
    {
        $this->sendResponse($data, $statusCode);
    }

    private function sendErrorResponse($message, $statusCode)
    {
        $responseData = ['error' => $message];
        $this->sendResponse($responseData, $statusCode);
    }

    private function sendResponse($data, $statusCode)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}


?>