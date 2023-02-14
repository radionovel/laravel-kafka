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

    public function handle(RlKafkaProducer $producer)
    {
        while(true) {
            /** @var Message[] $messages */
            $messages = Message::query()
                               ->where('status', 'pending')
                               ->limit(20)
                               ->get();

            foreach ($messages as $message) {
                if (RD_KAFKA_RESP_ERR_NO_ERROR === $producer->produce($message->topic, $message->payload, $message->key, $message->event_type)){
                    $message->update(['status' => 'completed']);
                }
            }

            $messageCount = Message::query()
                   ->where('status', 'pending')
                   ->count();

            if ($messageCount === 0) {
                sleep(5);
            }
        }
    }
}
