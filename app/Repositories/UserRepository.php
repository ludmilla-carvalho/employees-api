<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    /**
     * Get model class name
     */
    protected function getModelClass(): string
    {
        return User::class;
    }
}
