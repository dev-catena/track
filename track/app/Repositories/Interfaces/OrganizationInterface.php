<?php

namespace App\Repositories\Interfaces;

interface OrganizationInterface
{
    public function all($search,$filterType,$filter);
    public function detail($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function organization_list();
    public function org_dashboard_stats($orgId);
    public function dockGraphStats($orgId);
    public function departmentGraphStats($orgId);

}
