<?php

use Illuminate\Support\Facades\Artisan;
use Shared\Infrastructure\Messaging\RabbitMQService;

Artisan::command('rabbitmq:consume', function () {
    $this->info("Starting RabbitMQ Consumer in User Service...");
    
    /** @var RabbitMQService $rabbitmq */
    $rabbitmq = app(RabbitMQService::class);
    
    $queue = 'user_order_notifications_queue';
    $exchange = 'order_exchange';
    $routingKey = 'order.created';
    
    $rabbitmq->consume(
        queue: $queue,
        exchange: $exchange,
        routingKey: $routingKey,
        exchangeType: 'direct',
        callback: function ($data) {
            $this->info("--------------------------------------------------");
            $this->info(" [x] Received message in User Service!");
            $this->info(" Event Type: " . ($data['event'] ?? 'Unknown'));
            $this->info(" Order ID: " . ($data['order_id'] ?? 'N/A'));
            $this->info(" Price: $" . ($data['price'] ?? '0'));
            $this->info(" User ID: " . ($data['user_id'] ?? 'N/A'));
            $this->info(" Timestamp: " . ($data['timestamp'] ?? 'N/A'));
            $this->info("--------------------------------------------------");
            
            // Return true to acknowledge message (ACK)
            return true;
        }
    );
})->purpose('Consume OrderCreated events from RabbitMQ');
