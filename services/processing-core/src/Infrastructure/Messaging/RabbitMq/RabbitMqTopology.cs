using Infrastructure.Config;
using RabbitMQ.Client;

namespace Infrastructure.Messaging.RabbitMq;

/// <summary>
/// Deklarise RabbitMQ exchange, glavnu queue, retry queue i DLQ.
/// </summary>
public sealed class RabbitMqTopology
{
    private readonly RabbitMqConfig config;
    private readonly RabbitMqConnectionFactory connectionFactory;

    public RabbitMqTopology(RabbitMqConfig config, RabbitMqConnectionFactory connectionFactory)
    {
        this.config = config;
        this.connectionFactory = connectionFactory;
    }

    /// <summary>
    /// Otvara konekciju i deklarise topologiju.
    /// </summary>
    public void Setup()
    {
        using var connection = connectionFactory.CreateConnection();
        using var channel = connection.CreateModel();

        Declare(channel);
    }

    /// <summary>
    /// Deklarise topologiju na prosledjenom RabbitMQ kanalu.
    /// </summary>
    public void Declare(IModel channel)
    {
        channel.ExchangeDeclare(
            exchange: config.Exchange,
            type: config.ExchangeType,
            durable: true,
            autoDelete: false
        );

        channel.QueueDeclare(
            queue: config.Queue,
            durable: true,
            exclusive: false,
            autoDelete: false
        );

        channel.QueueBind(
            queue: config.Queue,
            exchange: config.Exchange,
            routingKey: config.RoutingKey
        );

        var retryArguments = new Dictionary<string, object>
        {
            ["x-message-ttl"] = config.RetryTtlMs,
            ["x-dead-letter-exchange"] = config.Exchange,
            ["x-dead-letter-routing-key"] = config.RoutingKey,
        };

        channel.QueueDeclare(
            queue: config.RetryQueue,
            durable: true,
            exclusive: false,
            autoDelete: false,
            arguments: retryArguments
        );

        channel.QueueDeclare(
            queue: config.DeadLetterQueue,
            durable: true,
            exclusive: false,
            autoDelete: false
        );
    }
}
