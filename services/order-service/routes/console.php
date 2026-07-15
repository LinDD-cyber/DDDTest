<?php

use Illuminate\Support\Facades\Artisan;
use Shared\Infrastructure\Messaging\RabbitMQService;

Artisan::command('rabbitmq:publish {orderId} {price} {userId}', function ($orderId, $price, $userId) {
    $this->info("Publishing Order Created Event for Order ID: {$orderId}...");
    
    /** @var RabbitMQService $rabbitmq */
    $rabbitmq = app(RabbitMQService::class);
    
    $eventData = [
        'event' => 'OrderCreated',
        'order_id' => $orderId,
        'price' => (float)$price,
        'user_id' => $userId,
        'timestamp' => now()->toIso8601String()
    ];
    
    // We will publish to exchange 'order_exchange' with routing key 'order.created'
    $rabbitmq->publish(
        exchange: 'order_exchange',
        routingKey: 'order.created',
        messageData: $eventData,
        exchangeType: 'direct'
    );
    
    $this->info("Successfully published message: " . json_encode($eventData, JSON_UNESCAPED_UNICODE));
})->purpose('Publish an OrderCreated event to RabbitMQ');
