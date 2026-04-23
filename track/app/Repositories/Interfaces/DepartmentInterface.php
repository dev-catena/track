<?php

namespace App\Repositories\Interfaces;

interface DepartmentInterface
{
    public function all($search,$filterType,$filter);
    public function detail($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function departmentsByCompanyId($id);

}
