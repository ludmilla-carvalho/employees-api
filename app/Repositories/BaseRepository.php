<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = $this->makeModel();
    }

    /**
     * Make model instance
     */
    protected function makeModel(): Model
    {
        $model = app($this->getModelClass());

        if (! $model instanceof Model) {
            throw new \Exception("Class {$this->getModelClass()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $model;
    }

    /**
     * Get model class name
     */
    abstract protected function getModelClass(): string;

    /**
     * Get all records
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    /**
     * Get records with pagination
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->paginate($perPage, $columns);
    }

    /**
     * Find record by id
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->model->find($id, $columns);
    }

    /**
     * Find by attribute
     */
    public function findBy(string $attribute, $value, array $columns = ['*']): ?Model
    {
        return $this->model->where($attribute, $value)->first($columns);
    }

    /**
     * Find all by attribute
     */
    public function findAllBy(string $attribute, $value, array $columns = ['*']): Collection
    {
        return $this->model->where($attribute, $value)->get($columns);
    }

    /**
     * Create new record
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update record
     */
    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    /**
     * Delete record
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Count records
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Check if record exists by attribute
     */
    public function exists(string $attribute, $value): bool
    {
        return $this->model->where($attribute, $value)->exists();
    }
}
