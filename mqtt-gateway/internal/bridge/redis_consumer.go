package bridge

import (
	"context"
	"encoding/json"
	"log"

	mqtt "github.com/eclipse/paho.mqtt.golang"
	"github.com/redis/go-redis/v9"
)

type RedisConsumer struct {
	rdb        *redis.Client
	mqttClient mqtt.Client
	queueKey   string
}

func NewRedisConsumer(rdb *redis.Client, mqttClient mqtt.Client, queueKey string) *RedisConsumer {
	return &RedisConsumer{rdb: rdb, mqttClient: mqttClient, queueKey: queueKey}
}

func (c *RedisConsumer) Start(ctx context.Context) {
	log.Printf("Redis Consumer started, listening on queue: %s", c.queueKey)
	for {
		select {
		case <-ctx.Done():
			return
		default:
			// 使用 BLPOP 阻塞式讀取，逾時設為 0 表示永久等待
			result, err := c.rdb.BLPop(ctx, 0, c.queueKey).Result()
			if err != nil {
				if err != context.Canceled {
					log.Printf("Redis BLPop error: %v", err)
				}
				continue
			}

			// result[0] 是 key，result[1] 是 value
			var cmd map[string]interface{}
			err = json.Unmarshal([]byte(result[1]), &cmd)
			if err != nil {
				log.Printf("Failed to unmarshal command: %v", err)
				continue
			}

			serialNo, ok := cmd["serial_no"].(string)
			if !ok || serialNo == "" {
				log.Printf("Invalid or missing serial_no in outgoing command: %v", string(result[1]))
				continue
			}

			// 將發送方才需要的欄位移除，保持 Payload 乾淨
			delete(cmd, "serial_no")
			delete(cmd, "timestamp")

			topic := "machine/" + serialNo + "/command"
			payload, _ := json.Marshal(cmd)

			token := c.mqttClient.Publish(topic, 1, false, payload)
			token.Wait()

			commandType, _ := cmd["command"].(string)
			log.Printf("Sent command [%s] to machine [%s] via topic: %s", commandType, serialNo, topic)
		}
	}
}
