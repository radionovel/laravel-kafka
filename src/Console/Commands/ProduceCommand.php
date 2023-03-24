<?php
declare(strict_types=1);

namespace RlKafka\Console\Commands;

use Illuminate\Console\Command;
use RlKafka\Models\Message;
use RlKafka\Producers\RlKafkaProducer;

class ProduceCommand extends Command
{
    protected $signature = 'rlkafka:produce';
    protected $description = 'Kafka consumer';

    /**
     * @param  \RlKafka\Producers\RlKafkaProducer  $producer
     *
     * @return void
     */
    public function handle(RlKafkaProducer $producer): void
    {
        while (true) {
            /** @var Message[] $messages */
            $messages = Message::query()
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->limit(10)
                ->get();

            foreach ($messages as $message) {
                if (RD_KAFKA_RESP_ERR_NO_ERROR === $producer->produce($message->topic, $message->payload, $message->key,
                        $message->event_type)) {
                    $message->update(['status' => 'completed']);
                }
            }

            if (count($messages) === 0) {
                sleep(5);
            }
        }
    }
}
