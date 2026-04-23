<?php
namespace App\Helpers;

use App\Models\SystemConfiguration;
use App\Models\User;
use App\Models\Operator;

class SystemConfigHelper
{
    /**
     * Retorna um timezone válido para PHP/Carbon.
     * 'us-central' (região GCP) não é válido; usa fallback.
     */
    public static function getSafeTimezone(?string $timezone): string
    {
        if (!$timezone) {
            return 'America/Sao_Paulo';
        }
        try {
            new \DateTimeZone($timezone);
            return $timezone;
        } catch (\Exception $e) {
            return 'America/Sao_Paulo';
        }
    }

    public static function getCurrentConfig()
    {
        $authUser = auth()->user();

        // If logged in user is NOT an operator → user exists in users table
        if ($authUser instanceof User) {

            // Case: superadmin/admin/manager
            if (in_array($authUser->role, ['superadmin', 'admin', 'manager'])) {

                return SystemConfiguration::where('user_id', $authUser->id)
                ->select('id','time_zone','date_format')
                ->first();
            }
        }

        // If logged-in user is an Operator (auth guard: operator or similar)
        if ($authUser instanceof Operator) {

            // Find manager in this department
            $manager = User::where('department_id', $authUser->department_id)
            ->where('role', 'manager')
            ->select('id')
            ->first();

            // If manager exists → get his settings
            if ($manager) {
                return SystemConfiguration::where('user_id', $manager->id)
                ->select('id','time_zone','date_format')
                ->first();
            }
        }

        return null;
    }
}
