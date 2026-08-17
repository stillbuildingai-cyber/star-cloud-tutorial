package main

import (
	"context"
	"log"
	"os"
	"os/signal"
	"star-cloud-gateway/config"
	"star-cloud-gateway/internal/bridge"
	"star-cloud-gateway/internal/handler"
	"syscall"

	"time"

	mqtt "github.com/eclipse/paho.mqtt.golang"
	"github.com/redis/go-redis/v9"
)

func main() {
	cfg := config.LoadConfig()

	var rdb *redis.Client
	var client mqtt.Client

	// 1. 初始化 Redis (帶重試機制)
	for {
		rdb = redis.NewClient(&redis.Options{
			Addr:     cfg.RedisAddr,
			Password: cfg.RedisPassword,
			DB:       0,
		})
		if err := rdb.Ping(context.Background()).Err(); err != nil {
			log.Printf("Waiting for Redis at %s... (%v)", cfg.RedisAddr, err)
			time.Sleep(5 * time.Second)
			continue
		}
		log.Printf("Connected to Redis at %s", cfg.RedisAddr)
		break
	}

	// 加入 Laravel 前綴，確保雙方桶子同一個
	prefixedIncomingKey := cfg.RedisPrefix + cfg.IncomingQueueKey
	pusher := bridge.NewRedisPusher(rdb, prefixedIncomingKey)
	mqttHandler := handler.NewMqttHandler(pusher)

	// 2. 初始化 MQTT (帶重試機制)
	opts := mqtt.NewClientOptions()
	opts.AddBroker(cfg.MQTTAddr)
	opts.SetClientID(cfg.MQTTClientID)
	opts.SetUsername(cfg.MQTTUser)
	opts.SetPassword(cfg.MQTTPassword)
	opts.SetCleanSession(true)
	opts.SetAutoReconnect(true)
	opts.SetMaxReconnectInterval(10 * time.Second)

	opts.SetOnConnectHandler(func(c mqtt.Client) {
		log.Printf("Connected to MQTT Broker as Gateway [%s] at %s", cfg.MQTTClientID, cfg.MQTTAddr)
		// 訂閱所有機台的上行 Topic (3 層: machine/{serial_no}/{type})
		token := c.Subscribe("machine/+/+", 1, mqttHandler.HandleMessage)
		token.Wait()

		// 訂閱 command/ack 子 Topic (4 層: machine/{serial_no}/command/ack)
		ackToken := c.Subscribe("machine/+/command/ack", 1, mqttHandler.HandleMessage)
		ackToken.Wait()

		if token.Error() != nil || ackToken.Error() != nil {
			log.Printf("Failed to subscribe to MQTT topics: %v %v", token.Error(), ackToken.Error())
		} else {
			log.Println("Successfully subscribed to machine/+/+ and machine/+/command/ack")
		}
	})

	client = mqtt.NewClient(opts)
	for {
		if token := client.Connect(); token.Wait() && token.Error() != nil {
			log.Printf("Waiting for MQTT Broker at %s... (%v)", cfg.MQTTAddr, token.Error())
			time.Sleep(5 * time.Second)
			continue
		}
		break
	}

	// 3. 啟動 Redis Consumer (下行指令)
	prefixedOutgoingKey := cfg.RedisPrefix + cfg.OutgoingQueueKey
	consumer := bridge.NewRedisConsumer(rdb, client, prefixedOutgoingKey)
	ctx, cancel := context.WithCancel(context.Background())
	go consumer.Start(ctx)

	// 4. 阻塞並監聽信號
	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, os.Interrupt, syscall.SIGTERM)
	<-sigChan

	log.Println("Shutting down Gateway...")
	cancel() // 停止 Consumer
	client.Disconnect(250)
	rdb.Close()
}
