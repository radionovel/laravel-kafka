<?php
declare(strict_types=1);

namespace RlKafka\Consumers;

use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RlKafka\Exceptions\RlKafkaConsumerException;
use RlKafka\Logger\Logger;

class RlKafkaConsumer
{

    private const IGNORABLE_CONSUMER_ERRORS = [
        RD_KAFKA_RESP_ERR__PARTITION_EOF,
        RD_KAFKA_RESP_ERR__TRANSPORT,
        RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT,
        RD_KAFKA_RESP_ERR__TIMED_OUT,
    ];

    private Logger $logger;
    protected int $consumerTimeOut;

    /**
     * @param  \RdKafka\KafkaConsumer  $consumer
     */
    public function __construct(private KafkaConsumer $consumer)
    {
        $this->logger = app(Logger::class);
        $this->consumerTimeOut = config('rlkafka.kafka.consumer_timeout_ms', 2000);
    }

    /**
     * @throws \RdKafka\Exception
     * @throws RlKafkaConsumerException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function consume()
    {
        $topics = $this->getTopics();

        if (count($topics) === 0) {
            $this->logger->error('Handlers have\'t configuration');
            return;
        }

        $this->consumer->subscribe(
            $this->getTopics()
        );

        while (true) {
            $message = $this->consumer->consume($this->consumerTimeOut);
            if (RD_KAFKA_RESP_ERR_NO_ERROR === $message->err) {
                $this->handleMessage($message);
                continue;
            }

            if (!in_array($message->err, self::IGNORABLE_CONSUMER_ERRORS)) {
                $this->logger->errorMessage($message);

                throw new RlKafkaConsumerException($message->errstr(), $message->err);
            }
        }
    }

    /**
     * @return array
     */
    private function getTopics(): array
    {
        $topics = array_keys($this->getHandlers());

        $topics = array_map(function ($topicWithEventType) {
            [$topic, ] = explode(':', $topicWithEventType);
            return $topic;
        }, $topics);

        return array_unique($topics);
    }

    /**
     * @return array
     */
    private function getHandlers(): array
    {
        return config('rlkafka.handlers', []);
    }

    /**
     * @param  \RdKafka\Message  $message
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \RdKafka\Exception
     */
    private function handleMessage(Message $message): void
    {
        $handlers = $this->getHandlers();
        $handlerIdentify = $this->resolveHandlerIdentifier($message);
        if (!array_key_exists($handlerIdentify, $handlers)) {
            return;
        }

        if (is_string($handlers[$handlerIdentify])) {
            $handler = app()->make($handlers[$handlerIdentify]);
            $payload = json_decode($message->payload, true);
            $handler->handle($payload);
            $this->consumer->commitAsync();
        }
    }

    /**
     * @param  \RdKafka\Message  $message
     *
     * @return string
     */
    private function resolveHandlerIdentifier(Message $message): string
    {
        if (isset($message->headers['event-type'])) {
            return "{$message->topic_name}:{$message->headers['event-type']}";
        }

        return $message->topic_name;
    }
}
