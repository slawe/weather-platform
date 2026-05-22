using Infrastructure.Config;
using Infrastructure.Database.Migrations;
using Infrastructure.Messaging.RabbitMq;
using Infrastructure.Weather;

var command = args.FirstOrDefault();
var databaseConfig = DatabaseConfig.FromEnvironment();
var rabbitMqConfig = RabbitMqConfig.FromEnvironment();
var connectionFactory = new RabbitMqConnectionFactory(rabbitMqConfig);
var topology = new RabbitMqTopology(rabbitMqConfig, connectionFactory);
var processor = new WeatherSnapshotFetchedProcessor(databaseConfig);
var historyCleanup = new WeatherHistoryRetentionCleanup(
    databaseConfig,
    WeatherHistoryRetentionConfig.FromEnvironment()
);

switch (command)
{
    case "migrate":
        await MigrationRunner.RunAsync();
        break;

    case "cleanup-history":
        var deletedRows = await historyCleanup.DeleteExpiredHistoryAsync();
        Console.WriteLine($"History cleanup completed. Deleted rows: {deletedRows}.");
        break;

    case "setup-rabbitmq":
        topology.Setup();
        Console.WriteLine("RabbitMQ topology setup completed.");
        break;

    case "consume":
        var consumer = new RabbitMqWeatherConsumer(rabbitMqConfig, connectionFactory, topology, processor);
        consumer.Consume();
        break;

    default:
        Console.WriteLine("Supported commands: migrate, cleanup-history, setup-rabbitmq, consume");
        break;
}
