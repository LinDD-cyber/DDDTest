<?php

namespace Shared\Infrastructure\Messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Exception;

class RabbitMQService
{
    protected ?AMQPStreamConnection $connection = null;
    protected $channel = null;

    public function __construct()
    {
        // Don't establish connection in constructor to avoid blocking if RabbitMQ is not used
    }

    /**
     * Get or create a connection
     */
    protected function getConnection(): AMQPStreamConnection
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $host = env('RABBITMQ_HOST', 'rabbitmq');
            $port = env('RABBITMQ_PORT', 5672);
            $user = env('RABBITMQ_USER', 'admin');
            $password = env('RABBITMQ_PASSWORD', 'admin123');

            $this->connection = new AMQPStreamConnection($host, $port, $user, $password);
            $this->channel = $this->connection->channel();
        }

        return $this->connection;
    }

    /**
     * Get the active channel
     */
    protected function getChannel()
    {
        $this->getConnection();
        return $this->channel;
    }

    /**
     * Publish a message to an exchange
     * 
     * @param string $exchange The exchange name
     * @param string $routingKey The routing key (optional)
     * @param array|string $messageData The message body
     * @param string $exchangeType The exchange type (direct, fanout, topic)
     */
    public function publish(string $exchange, string $routingKey, $messageData, string $exchangeType = 'direct'): void
    {
        $channel = $this->getChannel();

        // Declare the exchange
        // passive: false, durable: true, auto_delete: false
        $channel->exchange_declare($exchange, $exchangeType, false, true, false);

        $body = is_array($messageData) ? json_encode($messageData, JSON_UNESCAPED_UNICODE) : $messageData;

        $msg = new AMQPMessage($body, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT // Make message persistent
        ]);

        $channel->basic_publish($msg, $exchange, $routingKey);
    }

    /**
     * Subscribe to a queue and consume messages
     * 
     * @param string $queue The queue name
     * @param string $exchange The exchange to bind to (optional)
     * @param string $routingKey The routing key to bind with (optional)
     * @param callable $callback The callback function when message is received
     * @param string $exchangeType The exchange type (optional, default 'direct')
     */
    public function consume(string $queue, ?string $exchange = null, ?string $routingKey = null, callable $callback, string $exchangeType = 'direct'): void
    {
        $channel = $this->getChannel();

        // Declare the queue
        // passive: false, durable: true, exclusive: false, auto_delete: false
        $channel->queue_declare($queue, false, true, false, false);

        // Bind the queue to exchange if specified
        if ($exchange !== null) {
            $channel->exchange_declare($exchange, $exchangeType, false, true, false);
            $channel->queue_bind($queue, $exchange, $routingKey ?? '');
        }

        // Set prefetch count to 1 (fair dispatch)
        $channel->basic_qos(null, 1, null);

        $wrappedCallback = function (AMQPMessage $msg) use ($callback) {
            try {
                $body = $msg->body;
                // Parse JSON if possible
                $data = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $payload = $data;
                } else {
                    $payload = $body;
                }

                // Execute the actual callback
                $result = call_user_func($callback, $payload, $msg);

                // Acknowledge the message if callback doesn't throw exception or returns false explicitly
                if ($result !== false) {
                    $msg->ack();
                } else {
                    // Requeue message if callback returned false
                    $msg->nack(false, true);
                }
            } catch (Exception $e) {
                // Reject message (don't requeue by default to avoid infinite loops, or log it)
                $msg->nack(false, false);
                // We can log error or let user see it
                echo "Error processing message: " . $e->getMessage() . "\n";
            }
        };

        // start consumer
        $channel->basic_consume($queue, '', false, false, false, false, $wrappedCallback);

        echo " [*] Waiting for messages in queue [{$queue}]. To exit press CTRL+C\n";

        // Keep loop running
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    public function __destruct()
    {
        try {
            if ($this->channel !== null) {
                $this->channel->close();
            }
            if ($this->connection !== null && $this->connection->isConnected()) {
                $this->connection->close();
            }
        } catch (Exception $e) {
            // Ignore connection teardown errors
        }
    }
}
