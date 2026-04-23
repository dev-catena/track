<?php

namespace App\Repositories;

use App\Models\SystemConfiguration;
use App\Repositories\Interfaces\ConfigurationInterface;
use DB;
use Illuminate\Support\Facades\Log;
use App\Models\BotConfiguration;
class ConfigurationRepository implements ConfigurationInterface
{

    public function find($id)
    {
        return SystemConfiguration::findOrFail($id);
    }

    public function create($organization)
    {
        $user = $organization->configuration()->create([
            'organization_id' => $organization->id,
            'created_by' => $organization->created_by,
            'updated_by' => $organization->updated_by,
        ]);
    }
    // Create a new system theme/configuration
    public function createTheme($data)
    {
        DB::beginTransaction();
        try {

            $setting = SystemConfiguration::create([
                'user_id'  => $data->user_id,
                'created_by' => $data->user_id,
                'updated_by' => $data->user_id,
            ]);
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('theme creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
        return $setting;
    }


    // Update system configuration
    public function update($id, $data)
    {
        DB::beginTransaction();
        try {

            $setting = SystemConfiguration::findOrFail($id);
            $setting->update($data);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('updation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
        return $setting;
    }

    public function bot_detail($id)
    {
        $BotConfiguration = BotConfiguration::select('id','key','value','type','category','description')
        ->where('id',$id)->first();

        return $BotConfiguration;
    }

    // Create a new bot configuration
    public function bot_create($data)
    {
        DB::beginTransaction();
        try {

            $BotConfiguration = BotConfiguration::create($data);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bot Configuration creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $BotConfiguration;

    }

    public function bot_update($id, $data)
    {
        DB::beginTransaction();
        try {

            $BotConfiguration = BotConfiguration::findOrFail($id);
            $BotConfiguration->update($data);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bot Configuration updation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
        return $BotConfiguration;
    }

    public function bot_delete($id)
    {
        DB::beginTransaction();
        try {

            $BotConfiguration = BotConfiguration::findOrFail($id);
            $BotConfiguration->delete();
            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bot Configuration failed to delete', ['error' => $e->getMessage()]);
            throw $e;
        }

    }
}
