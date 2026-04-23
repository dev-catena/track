<?php

namespace App\Repositories\Interfaces;

interface ActivityLogInterface
{
    public function all($search, $entity, $action,$user,$organization_id,$department_id);
    public function create($data);
    public function cardData($user);
}
