<?php

class CategoryController
{
    public static function index(): false|string
    {
        return json_encode([
            'categories' => ['Categories returned']
        ], JSON_THROW_ON_ERROR);
    }

    public function getProductsByCategory($params): false|string
    {
        return json_encode([
            'category' => $params['category_name'],
            'products' => ['Product 1', 'Product 2']
        ], JSON_THROW_ON_ERROR);
    }
}