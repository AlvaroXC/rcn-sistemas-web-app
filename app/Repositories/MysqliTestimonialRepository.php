<?php

namespace RcnSistemas\Repositories;

use mysqli;
use RcnSistemas\Models\Testimonial;

final class MysqliTestimonialRepository implements TestimonialRepository
{
    public function __construct(private readonly mysqli $connection) {}

    public function all(): array
    {
        $result = $this->connection->query('SELECT * FROM testimonials ORDER BY date DESC');
        $testimonials = [];
        while ($result && $row = $result->fetch_assoc()) {
            $testimonials[] = Testimonial::fromArray($row);
        }

        return $testimonials;
    }

    public function create(array $data): ?Testimonial
    {
        $name = trim((string) ($data['name'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));
        $rating = (int) ($data['rating'] ?? 5);
        $date = $data['date'] ?? date('Y-m-d');
        $statement = $this->connection->prepare('INSERT INTO testimonials (name, message, rating, date) VALUES (?, ?, ?, ?)');
        $statement->bind_param('ssis', $name, $message, $rating, $date);

        return $statement->execute()
            ? Testimonial::fromArray(['id' => $this->connection->insert_id] + $data)
            : null;
    }
}
