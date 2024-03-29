<?php
declare(strict_types=1);


namespace RlKafka\Producers;

use RdKafka\Producer;
use RlKafka\Models\Message;

class RlKafkaProducer
{
    const FLUSH_TIMEOUT = 1000;

    public function __construct(private readonly Producer $producer)
    {
    }

    /**
     * @param  string  $topic
     * @param  array  $payload
     * @param  string|null  $key
     * @param  string  $eventType
     *
     * @return int
     */
    public function produce(string $topic, array $payload, ?string $key = null, string $eventType = ''): int
    {
        $topic = $this->producer->newTopic($topic);
        $headers = $this->prepareHeaders($eventType);
        $topic->producev(RD_KAFKA_PARTITION_UA, 0, json_encode($payload), $key, $headers);
        return $this->producer->flush(self::FLUSH_TIMEOUT);
    }

    /**
     * @param  string  $eventType
     *
     * @return array
     */
    private function prepareHeaders(string $eventType): array
    {
        $headers = [];
        if (trim($eventType) !== '') {
            $headers['event-type'] = $eventType;
        }

        return $headers;
    }

    /**
     * @param  string  $topic
     * @param  array  $payload
     * @param  string|null  $key
     * @param  string  $eventType
     *
     * @return void
     */
    public function produceAsync(string $topic, array $payload, ?string $key = null, string $eventType = ''): void
    {
        Message::create([
            'topic'      => $topic,
            'payload'    => $payload,
            'key'        => $key,
            'event_type' => $eventType,
            'status'     => 'pending',
        ]);
    }
}
