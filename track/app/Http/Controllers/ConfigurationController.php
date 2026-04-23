<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemConfiguration;
use App\Models\Organization;
use App\Models\BotConfiguration;
use Illuminate\Support\Facades\Auth;
use stdClass;
use App\Repositories\Interfaces\ConfigurationInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class ConfigurationController extends Controller
{

    protected $configuration;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    // Display system configuration settings
    public function index()
    {
        // Logic for displaying configuration settings

        $user  = Auth::user();

        $setting = SystemConfiguration::where('user_id',$user->id)->first();

        if(!$setting) {

            $data = new stdClass();
            $data->user_id = $user->id;
            $setting = $this->configuration->createTheme($data);

        }

        return view('configuration.index',compact('setting'));
    }

    // Update system configuration settings
    public function update(Request $request, $id)
    {
        try {

            $data = $request->validate([
                'theme'             => 'required|string',
                'user_id'           => 'required',
                'time_zone'         => 'required|string',
                'language'          => 'required|string',
                'date_format'       => 'required',
            ]);

            $data['updated_by'] = Auth::id();
            $setting  = $this->configuration->update($id, $data);

            return $this->sendJsonResponse(1,'Details updated successfully.',$setting);

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Display bot configuration settings
    public function bot(Request $request)
    {
        $user = Auth::user();
        if ($request->ajax()) {
            $bot_settings = BotConfiguration::select(['id', 'key', 'value', 'type', 'category', 'description', 'updated_at']);

            return DataTables::of($bot_settings)
                ->addColumn('type', function ($bot) {
                    return strtoupper($bot->type);
                })
                // ->editColumn('updated_at',function($bot){
                //     return Carbon::parse($bot->updated_at)->format('d/m/Y H:i:s');
                // })
                ->addColumn('actions', function ($bot) {
                    return '
                        <div class="dropdown">
                            <button type="button" class="btn btn-link dropdown-toggle btn-icon" data-toggle="dropdown">
                                <i class="fa fa-ellipsis-h"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right rounded-4 p-0">
                                <a class="dropdown-item m-0 rounded-4" href="javascript:void(0);" onclick="getBotDetail('.$bot->id.')">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                                <a class="dropdown-item m-0 rounded-4" href="javascript:void(0);" onclick="deleteBot('.$bot->id.')">
                                    <i class="text-danger fa fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    ';
                })
                ->rawColumns(['actions']) // allow HTML in 'actions'
                ->make(true);
        }
        return view('configuration.bot.index',compact('user'));
    }

    // Store a new bot configuration
    public function bot_store(Request $request)
    {
        try {
            $data = $request->validate([
                'key'               => 'required|string|regex:/^[A-Za-z_]+$/',
                'value'             => 'required',
                'type'              => 'required|string|in:number,string',
                'category'          => 'nullable|string|in:alert,notification,device,dock',
                'description'       => 'nullable|string',
            ]);

            $this->validateBotValue($data['key'], $data['value']);

            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['department_id'] = null; // Escopo global

            $bot = $this->configuration->bot_create($data);

            return $this->sendJsonResponse(1,'Bot Configuration created successfully.');

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Retrieve specific bot configuration details
    public function bot_detail(Request $request, $id) {
        $detail = $this->configuration->bot_detail($id);

        if($detail) {
            return $this->sendJsonResponse(1,'Bot detail retrieved successfully.',$detail);
        } else {
            return $this->sendJsonResponse(0,'No data found!');
        }
    }

    // Update an existing bot configuration
    public function bot_update(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'key'               => 'required|string|regex:/^[A-Za-z_]+$/',
                'value'             => 'required',
                'type'              => 'required|string|in:number,string',
                'category'          => 'nullable|string|in:alert,notification,device,dock',
                'description'       => 'nullable|string',
            ]);

            $this->validateBotValue($data['key'], $data['value']);

            $data['updated_by'] = Auth::id();
            $this->configuration->bot_update($id, $data);

            return $this->sendJsonResponse(1,'Bot Configuration updated successfully.');

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Delete a bot configuration
    public function bot_destroy($id)
    {
        $this->configuration->bot_delete($id);
        return $this->sendJsonResponse(1,'Bot Configuration deleted successfully.');
    }

    /**
     * Valida valor conforme regras da key (min/max).
     */
    private function validateBotValue(string $key, $value): void
    {
        $rules = [
            'max_checkout_hours_device' => ['numeric', 'min:1', 'max:168'],
            'lost_device_timeout_days' => ['numeric', 'min:1', 'max:90'],
            'delay_retry_interval_minutes' => ['numeric', 'min:5', 'max:60'],
            'reminder_percentages' => ['string', 'regex:/^(\d{2})(,\s*\d{2})*$/'], // ex: 90,99 ou 80,90,99
        ];

        if (!isset($rules[$key])) {
            return;
        }

        $validator = Validator::make(['value' => $value], ['value' => $rules[$key]]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Validação extra para reminder_percentages: cada número entre 50 e 99
        if ($key === 'reminder_percentages') {
            $parts = array_map('intval', array_map('trim', explode(',', $value)));
            foreach ($parts as $p) {
                if ($p < 50 || $p > 99) {
                    throw ValidationException::withMessages([
                        'value' => ['Cada percentual deve estar entre 50 e 99.'],
                    ]);
                }
            }
        }
    }
}
