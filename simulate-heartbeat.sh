#!/bin/bash

# =================================================================
# Star Cloud MQTT Heartbeat Simulator
# =================================================================
# 用於模擬機台每 10 秒發送一次心跳包。
# 依賴：Docker (使用 eclipse-mosquitto 鏡像進行發送)
# =================================================================

SERIAL_NO="${1:-SW-DEMO-001}"
API_TOKEN="${2:-tutorial-demo-token}"
TOPIC="machine/${SERIAL_NO}/heartbeat"
NETWORK="star-cloud_sail"
BROKER_HOST="emqx"

echo "🚀 開始模擬心跳發報 [${SERIAL_NO}]..."
echo "📡 頻率：每 10 秒一次"
echo "📝 Topic: ${TOPIC}"
echo "-------------------------------------------------"

while true; do
    TIMESTAMP=$(date +"%Y-%m-%dT%H:%M:%S+08:00")
    PAYLOAD="{\"current_page\":2,\"firmware_version\":\"1.0.6\",\"temperature\":$(awk "BEGIN {srand(); print 20+rand()*10}")}"
    
    echo "[${TIMESTAMP}] 正在發送心跳..."
    
    docker run --rm --network ${NETWORK} eclipse-mosquitto \
        mosquitto_pub -h ${BROKER_HOST} -p 1883 \
        -u "${SERIAL_NO}" -P "${API_TOKEN}" \
        -t "${TOPIC}" -m "${PAYLOAD}"
        
    if [ $? -eq 0 ]; then
        echo "✅ 發送成功"
    else
        echo "❌ 發送失敗，請檢查 Docker 網路或 EMQX 狀態"
    fi
    
    echo "💤 等待 10 秒..."
    sleep 10
done
