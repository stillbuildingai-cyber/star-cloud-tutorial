package config

import (
	"os"
)

type Config struct {
	MQTTAddr         string
	MQTTClientID     string
	MQTTUser         string
	MQTTPassword     string
	RedisAddr        string
	RedisPassword    string
	IncomingQueueKey string
	OutgoingQueueKey string
	RedisPrefix      string
}

func LoadConfig() *Config {
	return &Config{
		MQTTAddr:         getEnv("MQTT_BROKER_ADDR", "tcp://emqx:1883"),
		MQTTClientID:     getEnv("MQTT_GATEWAY_CLIENT_ID", "star-cloud-gateway"),
		MQTTUser:         getEnv("MQTT_USER", "star-cloud-gateway"),
		MQTTPassword:     getEnv("MQTT_PASSWORD", "tutorial-gateway-secret"),
		RedisAddr:        getEnv("MQTT_REDIS_ADDR", "star-cloud-redis:6379"),
		RedisPassword:    getEnv("MQTT_REDIS_PASSWORD", ""),
		IncomingQueueKey: getEnv("MQTT_INCOMING_QUEUE", "mqtt_incoming_jobs"),
		OutgoingQueueKey: getEnv("MQTT_OUTGOING_QUEUE", "mqtt_outgoing_commands"),
		RedisPrefix:      getEnv("MQTT_REDIS_PREFIX", "star_cloud_database_"),
	}
}

func getEnv(key, fallback string) string {
	if value, ok := os.LookupEnv(key); ok {
		return value
	}
	return fallback
}
