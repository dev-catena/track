<?php

namespace App\Repositories\Interfaces;

interface ConfigurationInterface
{

    public function find($id);

    public function create(object $data);
    public function createTheme(object $data);

    public function update($id, array $data);

    public function bot_detail($id);
    public function bot_create(array $data);
    public function bot_update($id, array $data);
    public function bot_delete($id);
}
