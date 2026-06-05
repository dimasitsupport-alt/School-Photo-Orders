<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Order
{
    public function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO orders (order_no, school_name, school_logo, school_address)
             VALUES (:order_no, :school_name, :school_logo, :school_address)'
        );

        $statement->execute([
            'order_no' => $data['order_no'],
            'school_name' => $data['school_name'],
            'school_logo' => $data['school_logo'],
            'school_address' => $data['school_address'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function findByOrderNo(string $orderNo): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, order_no, school_name, school_logo, school_address, created_at
             FROM orders
             WHERE order_no = :order_no
             LIMIT 1'
        );
        $statement->execute(['order_no' => $orderNo]);
        $order = $statement->fetch();

        return $order ?: null;
    }

    public function findByOrderNoWithCount(string $orderNo): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT o.id, o.order_no, o.school_name, o.school_logo, o.school_address, o.created_at,
                    (
                        SELECT COUNT(*)
                        FROM students s
                        WHERE s.order_id = o.id
                    ) AS photo_count
             FROM orders o
             WHERE o.order_no = :order_no
             LIMIT 1'
        );
        $statement->execute(['order_no' => $orderNo]);
        $order = $statement->fetch();

        return $order ?: null;
    }

    public function exists(string $orderNo): bool
    {
        $statement = Database::connection()->prepare('SELECT 1 FROM orders WHERE order_no = :order_no LIMIT 1');
        $statement->execute(['order_no' => $orderNo]);

        return (bool) $statement->fetchColumn();
    }

    public function searchWithPhotoCount(string $query, int $limit = 10): array
    {
        $statement = Database::connection()->prepare(
            'SELECT o.id, o.order_no, o.school_name, o.school_logo, o.school_address, o.created_at,
                    (
                        SELECT COUNT(*)
                        FROM students s
                        WHERE s.order_id = o.id
                    ) AS photo_count
             FROM orders o
             WHERE o.order_no LIKE :query
             ORDER BY o.created_at DESC
             LIMIT :limit'
        );

        $statement->bindValue(':query', '%' . $query . '%');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
