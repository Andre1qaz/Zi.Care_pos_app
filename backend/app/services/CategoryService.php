<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use Phalcon\Di\Di;

class CategoryService
{
    private CategoryRepository $repository;

    public function __construct()
    {
        $this->repository = Di::getDefault()->getShared('categoryRepository');
    }

    public function list(): array
    {
        return $this->repository->findAll();
    }

    public function get(int $id): array
    {
        $category = $this->repository->findById($id);
        if (!$category) {
            throw new NotFoundException('Category not found');
        }
        return $category->toArray();
    }

    public function create(array $data): array
    {
        if (empty($data['category_name'])) {
            throw new ValidationException(['category_name' => 'Category name is required']);
        }

        $category = new Category();
        $category->assign($data);
        $category->save();

        return $category->toArray();
    }

    public function update(int $id, array $data): array
    {
        $category = $this->repository->findById($id);
        if (!$category) {
            throw new NotFoundException('Category not found');
        }

        $category->assign($data);
        $category->save();

        return $category->toArray();
    }

    public function delete(int $id): void
    {
        $category = $this->repository->findById($id);
        if (!$category) {
            throw new NotFoundException('Category not found');
        }
        $category->delete();
    }
}
