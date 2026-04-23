<?php

namespace App\Repositories\Interfaces;

interface UserInterface
{

    public function find($id);

    public function createOrganizationAdmin(object $organization);
    public function updateOrganizationAdmin(object $organization);

    public function all($search, $filterType, $filter, $user, $organizationId = null);
    public function detail($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
