<?php

namespace App\Repositories\Interfaces;

interface DockInterface
{
    public function all($search, $filterType, $filter, $user, $organizationId = null);
    public function detail($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id,$authId);
    public function dockStats($user);
    public function get_mqtt_topics($dock_id);
}
