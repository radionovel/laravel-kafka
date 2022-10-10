<?php
declare(strict_types=1);

namespace RlKafka\Consumers;

use RlKafka\Exceptions\RlKafkaConsumerException;
use RlKafka\Logger\Logger;
use Radionovel\Hydrator\Hydrator;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use ReflectionNamedType;

class RlKafkaConsumer
{

    private const IGNORABLE_CONSUMER_ERRORS = [
        RD_KAFKA_RESP_ERR__PARTITION_EOF,
        RD_KAFKA_RESP_ERR__TRANSPORT,
        RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT,
        RD_KAFKA_RESP_ERR__TIMED_OUT,
    ];

    private Logger $logger;

    /**
     * @param  \RdKafka\KafkaConsumer  $consumer
     * @param  \Radionovel\Hydrator\Hydrator  $hydrator
     */
    public function __construct(private KafkaConsumer $consumer, private Hydrator $hydrator)
    {
        $this->logger = app(Logger::class);
    }

    /**
     * @throws \RdKafka\Exception
     * @throws \App\Exceptions\RlKafkaConsumerException
     */
    public function consume()
    {
        $this->consumer->subscribe(
            $this->getTopics()
        );

        while (true) {
            $message = $this->consumer->consume(config('rlkafka.kafka.consumer_timeout_ms', 2000));
            if (RD_KAFKA_RESP_ERR_NO_ERROR === $message->err) {
                $this->handleMessage($message);
                continue;
            }

            if (!in_array($message->err, self::IGNORABLE_CONSUMER_ERRORS)) {
                $this->logger->error($message);

                throw new RlKafkaConsumerException($message->errstr(), $message->err);
            }
        }
    }

    /**
     * @return array
     */
    private function getTopics(): array
    {
        return config('rlkafka.handlers');
    }

    /**
     * @param  \RdKafka\Message  $message
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    private function handleMessage(Message $message): void
    {
        $topics = $this->getTopics();
        $handlerIdentify = $this->resolveHandlerIdentifier($message);

        if (!array_key_exists($handlerIdentify, $topics)) {
            return;
        }

        if (is_string($topics[$handlerIdentify])) {
            $handler = app()->make($topics[$handlerIdentify]);
            $payload = $this->makeTypedPayload($handler, $message->payload);
            $handler->handle($payload);
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

    /**
     * @param  object  $handler
     *
     * @return string|null
     * @throws \ReflectionException
     */
    private function getHandlerParameterType(object $handler): ?string
    {
        $reflection = new \ReflectionClass($handler);
        $methodReflection = $reflection->getMethod('handle');
        $parameters = $methodReflection->getParameters();

        if (count($parameters) === 0) {
            return null;
        }

        $parameter = $parameters[0]->getType();
        $types = $parameter instanceof ReflectionNamedType ? [$parameter] : $parameter->getTypes();

        foreach ($types as $type) {
            if ($type->isBuiltin() === false) {
                return $type->getName();
            }
        }

        return null;
    }

    /**
     * @param  mixed  $handler
     * @param  string  $payload
     *
     * @return mixed
     * @throws \ReflectionException
     */
    private function makeTypedPayload(mixed $handler, string $payload): mixed
    {
        $handlerParameterType = $this->getHandlerParameterType($handler);
        $payload = json_decode($payload, true);
        return $handlerParameterType ? $this->hydrator->hydrate($handlerParameterType, $payload) : $payload;
    }
}
