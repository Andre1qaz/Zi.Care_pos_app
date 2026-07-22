<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;

class CategoryRepository extends BaseRepository
{
    public function findAll(): array
    {
        return Category::find(['order' => 'category_name ASC'])->toArray();
    }

    public function findById(int $id): ?Category
    {
        return Category::findFirst($id) ?: null;
    }
}
