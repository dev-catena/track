<?php

namespace App\Repositories\Interfaces;

interface ProfileInterface
{
    public function all($search = '');
    public function detail($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function listForUserForm(string $currentUserRole);
}
