<?php
namespace App\Services;

use App\Repositories\PadronRepository;

class PadronService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new PadronRepository();
    }

    public function queryPadron($query, $municipio, $page, $limit)
    {
        return $this->repository->search($query, $municipio, $page, $limit);
    }
}
