<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Student
{
    public function bulkCreate(int $orderId, array $students): void
    {
        if ($students === []) {
            return;
        }

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $statement = $connection->prepare(
                'INSERT INTO students (order_id, student_name, photo_path)
                 VALUES (:order_id, :student_name, :photo_path)'
            );

            foreach ($students as $student) {
                $statement->execute([
                    'order_id' => $orderId,
                    'student_name' => $student['student_name'],
                    'photo_path' => $student['photo_path'],
                ]);
            }

            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    public function allByOrderId(int $orderId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, student_name, photo_path, created_at
             FROM students
             WHERE order_id = :order_id
             ORDER BY id ASC'
        );
        $statement->execute(['order_id' => $orderId]);

        return $statement->fetchAll();
    }
}
