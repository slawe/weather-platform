using System.Text;
using System.Text.Json;
using Application.Messaging.DTO;
using Application.Messaging.Validation;
using Infrastructure.Config;
using Infrastructure.Weather;
using RabbitMQ.Client;
using RabbitMQ.Client.Events;

namespace Infrastructure.Messaging.RabbitMq;

/// <summary>
/// RabbitMQ consumer za weather.snapshot.fetched evente.
/// </summary>
public sealed class RabbitMqWeatherConsumer
{
    private readonly RabbitMqConfig config;
    private readonly RabbitMqConnectionFactory connectionFactory;
    private readonly RabbitMqTopology topology;
    private readonly WeatherSnapshotFetchedProcessor processor;
    private readonly JsonSerializerOptions jsonOptions = new(JsonSerializerDefaults.Web);

    public RabbitMqWeatherConsumer(
        RabbitMqConfig config,
        RabbitMqConnectionFactory connectionFactory,
        RabbitMqTopology topology,
        WeatherSnapshotFetchedProcessor processor
    )
    {
        this.config = config;
        this.connectionFactory = connectionFactory;
        this.topology = topology;
        this.processor = processor;
    }

    /// <summary>
    /// Pokrece blocking consume petlju dok se ne zatrazi gasenje.
    /// </summary>
    public void Consume(CancellationToken cancellationToken = default)
    {
        using var shutdown = new ManualResetEventSlim(false);
        using var connection = connectionFactory.CreateConnection();
        using var channel = connection.CreateModel();
        using var cancellationRegistration = cancellationToken.Register(shutdown.Set);

        topology.Declare(channel);

        channel.BasicQos(prefetchSize: 0, prefetchCount: 1, global: false);

        var consumer = new EventingBasicConsumer(channel);

        consumer.Received += (_, eventArgs) => HandleMessage(channel, eventArgs);

        channel.BasicConsume(
            queue: config.Queue,
            autoAck: false,
            consumer: consumer
        );

        Console.WriteLine($"Processing-core consumer sluša queue: {config.Queue}");
        Console.CancelKeyPress += (_, eventArgs) =>
        {
            eventArgs.Cancel = true;
            shutdown.Set();
        };

        shutdown.Wait();
    }

    /// <summary>
    /// Obradjuje jednu RabbitMQ poruku.
    /// </summary>
    private void HandleMessage(IModel channel, BasicDeliverEventArgs eventArgs)
    {
        var body = Encoding.UTF8.GetString(eventArgs.Body.ToArray());

        try
        {
            var envelope = Deserialize(body);
            WeatherSnapshotFetchedValidator.Validate(envelope);

            var processed = processor.ProcessAsync(envelope).GetAwaiter().GetResult();

            if (!processed)
            {
                Console.WriteLine($"Duplikat eventa {envelope.EventId}. Poruka je potvrđena bez ponovne obrade.");
                channel.BasicAck(eventArgs.DeliveryTag, multiple: false);

                return;
            }

            Console.WriteLine(
                $"Obrađen event {envelope.EventName} ({envelope.EventId}) za {envelope.Payload!.City} / {envelope.Payload.Source}"
            );

            channel.BasicAck(eventArgs.DeliveryTag, multiple: false);
        }
        catch (Exception exception)
        {
            Console.WriteLine($"Greška pri obradi RabbitMQ poruke: {exception.Message}");
            HandleFailure(channel, eventArgs, body);
        }
    }

    /// <summary>
    /// Deserializuje canonical event envelope.
    /// </summary>
    private WeatherSnapshotFetchedEnvelope Deserialize(string body)
    {
        var envelope = JsonSerializer.Deserialize<WeatherSnapshotFetchedEnvelope>(body, jsonOptions);

        return envelope ?? throw new InvalidOperationException("RabbitMQ poruka nema validan JSON envelope.");
    }

    /// <summary>
    /// Salje neuspesnu poruku u retry ili DLQ tok.
    /// </summary>
    private void HandleFailure(IModel channel, BasicDeliverEventArgs eventArgs, string body)
    {
        var retryCount = CurrentRetryCount(eventArgs.BasicProperties);
        var nextRetryCount = retryCount + 1;
        var targetQueue = nextRetryCount >= config.MaxRetries
            ? config.DeadLetterQueue
            : config.RetryQueue;

        var properties = channel.CreateBasicProperties();
        properties.Persistent = true;
        properties.ContentType = eventArgs.BasicProperties.ContentType ?? "application/json";
        properties.MessageId = eventArgs.BasicProperties.MessageId;
        properties.Type = eventArgs.BasicProperties.Type;
        properties.Headers = CopyHeaders(eventArgs.BasicProperties.Headers);
        properties.Headers["x-retry-count"] = nextRetryCount;

        channel.BasicPublish(
            exchange: string.Empty,
            routingKey: targetQueue,
            basicProperties: properties,
            body: Encoding.UTF8.GetBytes(body)
        );

        channel.BasicAck(eventArgs.DeliveryTag, multiple: false);
    }

    /// <summary>
    /// Cita trenutni retry count iz RabbitMQ headera.
    /// </summary>
    private static int CurrentRetryCount(IBasicProperties properties)
    {
        if (properties.Headers is null || !properties.Headers.TryGetValue("x-retry-count", out var value))
        {
            return 0;
        }

        return value switch
        {
            int intValue => intValue,
            long longValue => (int)longValue,
            byte[] bytes => int.TryParse(Encoding.UTF8.GetString(bytes), out var parsedValue) ? parsedValue : 0,
            _ => 0,
        };
    }

    /// <summary>
    /// Kopira RabbitMQ headere u novi dictionary.
    /// </summary>
    private static Dictionary<string, object> CopyHeaders(IDictionary<string, object>? headers)
    {
        return headers is null
            ? new Dictionary<string, object>()
            : new Dictionary<string, object>(headers);
    }
}
