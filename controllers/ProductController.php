<?php

class ProductController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function createProduct()
    {
        $data = $_POST;

        if (empty($data['name']) || empty($data['weight']) || empty($data['availability'])) {
            $this->sendErrorResponse('Missing required data', 400);
        }

        $product = [
            'name' => $data['name'],
            'weight' => $data['weight'],
            'availability' => $data['availability']
        ];

        $query = $this->db->prepare("INSERT INTO Products (name, weight, availability) VALUES (:name, :weight, :availability)");
        $query->execute($product);

        $responseData = [
            'message' => 'Product created successfully',
            'product_id' => $this->db->lastInsertId()
        ];
        $this->sendSuccessResponse($responseData, 200);
    }

    public function getProduct()
    {
        $productId = $_GET['id'];

        $query = $this->db->prepare("SELECT * FROM Products WHERE id = :id");
        $query->execute(['id' => $productId]);
        $product = $query->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $this->sendErrorResponse('Product not found', 404);
        }

        $this->sendSuccessResponse($product, 200);
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
        exit();
    }
}

?>