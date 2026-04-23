<?php

namespace App\Repositories\Interfaces;

interface DeviceInterface
{
    public function all($search,$status,$dock,$user);
    public function detail($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id,$authId);
    public function checkout($id,$authId,$lat,$long,$fcm_token);
    public function checkin($id,$authId,$lat,$long,$fcm_token);
    public function captureDeviceLocation(array $data);
}
