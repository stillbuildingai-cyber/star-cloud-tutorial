package bridge

import (
	"context"
	"encoding/json"
	"log"
	"time"

	"github.com/redis/go-redis/v9"
)

type RedisPusher struct {
	client *redis.Client
	key    string
}

type BridgePayload struct {
	Type       string      `json:"type"`
	SerialNo   string      `json:"serial_no"`
	Payload    interface{} `json:"payload"`
	ReceivedAt string      `json:"received_at"`
}

func NewRedisPusher(client *redis.Client, key string) *RedisPusher {
	return &RedisPusher{client: client, key: key}
}

func (p *RedisPusher) Push(payload BridgePayload) error {
	payload.ReceivedAt = time.Now().Format(time.RFC3339)
	data, err := json.Marshal(payload)
	if err != nil {
		return err
	}

	ctx := context.Background()
	err = p.client.RPush(ctx, p.key, data).Err()
	if err != nil {
		log.Printf("Failed to push to Redis: %v", err)
	}
	return err
}
