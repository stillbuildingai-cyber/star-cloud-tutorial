package handler

import (
	"encoding/json"
	"log"
	"star-cloud-gateway/internal/bridge"
	"strings"

	mqtt "github.com/eclipse/paho.mqtt.golang"
)

type MqttHandler struct {
	pusher *bridge.RedisPusher
}

func NewMqttHandler(pusher *bridge.RedisPusher) *MqttHandler {
	return &MqttHandler{pusher: pusher}
}

func (h *MqttHandler) HandleMessage(client mqtt.Client, msg mqtt.Message) {
	topic := msg.Topic()
	payload := msg.Payload()

	log.Printf("Received message on topic: %s", topic)

	// 業務主題格式：
	// 3 層: machine/{serialNo}/{type} (如 heartbeat, error, transaction, status)
	// 4 層: machine/{serialNo}/{type}/{subType} (如 command/ack)
	parts := strings.Split(topic, "/")
	if len(parts) < 3 {
		return
	}

	serialNo := parts[1]
	msgType := parts[2]

	// 4 層 Topic：將 type/subType 組合為 type_subType (如 command/ack → command_ack)
	if len(parts) >= 4 {
		msgType = parts[2] + "_" + parts[3]
	}

	var jsonPayload interface{}
	err := json.Unmarshal(payload, &jsonPayload)
	if err != nil {
		// 如果不是有效的 JSON，則視為純文字/數值 (Raw Value)
		jsonPayload = string(payload)
	}

	bridgePayload := bridge.BridgePayload{
		Type:     msgType,
		SerialNo: serialNo,
		Payload:  jsonPayload,
	}

	h.pusher.Push(bridgePayload)
}
