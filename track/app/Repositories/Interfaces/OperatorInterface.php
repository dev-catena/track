<?php

namespace App\Repositories\Interfaces;

interface OperatorInterface
{

    public function find($id);

    //public function all($search,$filterType,$filter);
    public function detail($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function facedetail($id);
    public function facedetailV2($id);
}
