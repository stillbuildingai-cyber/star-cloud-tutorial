{{-- 8090 後台系統機台列表 Blade 視圖範例 (machines/index.blade.php) --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="m-0"><i class="fa-solid fa-plug"></i> 機台管理 \ 智慧插座機台列表</h5>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>機台名稱 / 位置</th>
                    <th>機台序號 (Machine ID)</th>
                    <th>連線狀態</th>
                    <th>繼電器電源</th>
                    <th>即時功率 (W)</th>
                    <th>即時電流 (A)</th>
                    <th>累計電量 (kWh)</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($machines as $machine)
                @php
                    $mName = is_array($machine) ? ($machine['name'] ?? '智慧插座') : ($machine->name ?? '智慧插座');
                    $mLoc = is_array($machine) ? ($machine['location'] ?? 'A區生產線') : ($machine->location ?? 'A區生產線');
                    $mId = is_array($machine) ? ($machine['machine_id'] ?? '未綁定') : ($machine->machine_id ?? '未綁定');
                    $mIp = is_array($machine) ? ($machine['socket_ip'] ?? '') : ($machine->socket_ip ?? '');
                    $mRelay = is_array($machine) ? ($machine['relay_state'] ?? false) : ($machine->relay_state ?? false);
                    $mPower = is_array($machine) ? ($machine['current_power'] ?? 0.0) : ($machine->current_power ?? 0.0);
                    $mAmp = is_array($machine) ? ($machine['current_amp'] ?? 0.0) : ($machine->current_amp ?? 0.0);
                    $mEnergy = is_array($machine) ? ($machine['total_energy'] ?? 0.0) : ($machine->total_energy ?? 0.0);
                    $mLastSeen = is_array($machine) ? ($machine['last_telemetry_at'] ?? null) : ($machine->last_telemetry_at ?? null);
                    
                    $isOnline = false;
                    if ($mLastSeen) {
                        $ts = is_numeric($mLastSeen) ? (int)$mLastSeen : strtotime($mLastSeen);
                        $isOnline = (time() - $ts) < 30;
                    }
                @endphp
                <tr>
                    <td>
                        <strong>{{ $mName }}</strong><br>
                        <small class="text-muted">{{ $mLoc }}</small>
                    </td>
                    <td><code class="text-primary font-monospace">{{ $mId }}</code></td>
                    <td>
                        @if($isOnline)
                            <span class="badge bg-success"><i class="fa-solid fa-circle"></i> 連線中</span>
                        @else
                            <span class="badge bg-secondary"><i class="fa-solid fa-circle"></i> 離線</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $mRelay ? 'bg-success' : 'bg-danger' }}">
                            {{ $mRelay ? 'ON (開啟)' : 'OFF (關閉)' }}
                        </span>
                    </td>
                    <td class="fw-bold text-info">{{ number_format((float)$mPower, 1) }} W</td>
                    <td>{{ number_format((float)$mAmp, 2) }} A</td>
                    <td>{{ number_format((float)$mEnergy, 3) }} kWh</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="controlRelay('{{ $mIp }}', 'toggle')">
                            <i class="fa-solid fa-power-off"></i> 切換電源
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">尚無機台紀錄。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
async function controlRelay(ip, action) {
    if (!ip) return alert('智慧插座未連線或未取得 IP');
    try {
        const res = await fetch(`http://${ip}/api/control`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: action })
        });
        alert('⚡ 已成功發送電源控制命令至插座！');
        location.reload();
    } catch (e) {
        alert('❌ 命令發送失敗，請確認與插座在同一 Wi-Fi 區網: ' + e);
    }
}
</script>
