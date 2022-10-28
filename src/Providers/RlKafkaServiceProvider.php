<?php
declare(strict_types=1);

namespace RlKafka\Providers;

use RlKafka\Console\Commands\ConsumeCommand;
use RlKafka\Console\Commands\ProduceCommand;
use RlKafka\Consumers\RlKafkaConsumer;
use RlKafka\Producers\RlKafkaProducer;
use Illuminate\Support\ServiceProvider;

class RlKafkaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->configurePublishing();
    }

    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(RlKafkaConsumer::class, function () {
            $options = config('rlkafka.kafka');
            $config = new \RdKafka\Conf();

            foreach ($options as $key => $value) {
                $config->set($key, $value);
            }

            $consumer = new \RdKafka\KafkaConsumer($config);
            return new RlKafkaConsumer($consumer);
        });

        $this->app->bind(RlKafkaProducer::class, function () {
            $options = config('rlkafka.kafka');
            $config = new \RdKafka\Conf();

            foreach ($options as $key => $value) {
                $config->set($key, $value);
            }

            $producer = new \RdKafka\Producer($config);
            return new RlKafkaProducer($producer);
        });
    }

    /**
     * Register the console commands for the package.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ConsumeCommand::class,
                ProduceCommand::class,
            ]);
        }
    }

    /**
     * @return void
     */
    private function configurePublishing()
    {
        $this->publishes([
            __DIR__."/../../config/rlkafka.php" => config_path('rlkafka.php'),
        ], 'rlkafka');
    }
}
