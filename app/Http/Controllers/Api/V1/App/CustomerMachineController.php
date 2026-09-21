<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Models\Machine\Machine;
use App\Models\Machine\RemoteCommand;
use App\Services\Machine\MqttService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerMachineController extends Controller
{
    protected MqttService $mqttService;

    public function __construct(MqttService $mqttService)
    {
        $this->mqttService = $mqttService;
    }

    /**
     * 這個 App 帳號被指派管理的機台清單（machine_user 關聯），
     * 而不是整間公司底下的全部機台——一般客戶只該看到自己的插座。
     */
    public function index(Request $request): JsonResponse
    {
        $machines = $request->user()->machines()
            ->orderByDesc('last_heartbeat_at')
            ->get(['machines.id', 'machines.name', 'machines.serial_no', 'machines.status', 'machines.last_heartbeat_at', 'machines.ambient_temperature']);

        return response()->json([
            'success' => true,
            'data' => $machines->map(fn (Machine $machine) => $this->transform($machine)),
        ]);
    }

    public function show(Request $request, Machine $machine): JsonResponse
    {
        $this->authorizeAccess($request, $machine);

        return response()->json([
            'success' => true,
            'data' => $this->transform($machine),
        ]);
    }

    /**
     * 遠端控制：跟 RemoteController::storeCommand() 同一套去重 + 派送邏輯，
     * 只是指令種類限縮成智慧插座相關的幾種，不開放販賣機專用的指令類型。
     */
    public function storeCommand(Request $request, Machine $machine): JsonResponse
    {
        $this->authorizeAccess($request, $machine);

        $validated = $request->validate([
            'command_type' => 'required|string|in:power_on,power_off,power_toggle,reboot',
        ]);

        RemoteCommand::where('machine_id', $machine->id)
            ->where('command_type', $validated['command_type'])
            ->where('status', 'pending')
            ->update([
                'status' => 'superseded',
                'note' => __('Superseded by new command'),
                'executed_at' => now(),
            ]);

        $command = RemoteCommand::create([
            'machine_id' => $machine->id,
            'user_id' => $request->user()->id,
            'command_type' => $validated['command_type'],
            'payload' => [],
            'status' => 'pending',
            'remark' => __('Triggered from customer app'),
        ]);

        $this->mqttService->pushCommand(
            $machine->serial_no,
            $command->command_type,
            $command->payload,
            (string) $command->id
        );

        return response()->json([
            'success' => true,
            'message' => __('Command has been queued successfully.'),
        ]);
    }

    /**
     * 確認這個 App 帳號確實被指派管理這台機台，
     * TenantScoped 只保證同一間公司，這裡再多一層「這台是不是真的分給他」的檢查。
     */
    protected function authorizeAccess(Request $request, Machine $machine): void
    {
        abort_unless(
            $request->user()->machines()->where('machines.id', $machine->id)->exists(),
            403
        );
    }

    protected function transform(Machine $machine): array
    {
        return [
            'id' => $machine->id,
            'name' => $machine->name,
            'serial_no' => $machine->serial_no,
            'is_online' => $machine->status === 'online',
            'ambient_temperature' => $machine->ambient_temperature,
            'last_heartbeat_at' => $machine->last_heartbeat_at,
        ];
    }
}
