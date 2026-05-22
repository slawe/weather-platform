namespace Infrastructure.Config;

/// <summary>
/// Cita RabbitMQ konfiguraciju iz environment promenljivih.
/// </summary>
public sealed class RabbitMqConfig
{
    public string Host { get; }

    public int Port { get; }

    public string User { get; }

    public string Password { get; }

    public string VHost { get; }

    public string Exchange { get; }

    public string ExchangeType { get; }

    public string Queue { get; }

    public string RetryQueue { get; }

    public string DeadLetterQueue { get; }

    public string RoutingKey { get; }

    public int RetryTtlMs { get; }

    public int MaxRetries { get; }

    private RabbitMqConfig(
        string host,
        int port,
        string user,
        string password,
        string vHost,
        string exchange,
        string exchangeType,
        string queue,
        string retryQueue,
        string deadLetterQueue,
        string routingKey,
        int retryTtlMs,
        int maxRetries
    )
    {
        Host = host;
        Port = port;
        User = user;
        Password = password;
        VHost = vHost;
        Exchange = exchange;
        ExchangeType = exchangeType;
        Queue = queue;
        RetryQueue = retryQueue;
        DeadLetterQueue = deadLetterQueue;
        RoutingKey = routingKey;
        RetryTtlMs = retryTtlMs;
        MaxRetries = maxRetries;
    }

    /// <summary>
    /// Kreira RabbitMQ config iz environment promenljivih.
    /// </summary>
    public static RabbitMqConfig FromEnvironment()
    {
        return new RabbitMqConfig(
            GetString("RABBITMQ_HOST", "rabbitmq"),
            GetInt("RABBITMQ_PORT", 5672),
            GetString("RABBITMQ_USER", "demo"),
            GetString("RABBITMQ_PASSWORD", "demo"),
            GetString("RABBITMQ_VHOST", "/"),
            GetString("RABBITMQ_EXCHANGE", "weather.events"),
            GetString("RABBITMQ_EXCHANGE_TYPE", "topic"),
            GetString("RABBITMQ_QUEUE", "weather.processing"),
            GetString("RABBITMQ_RETRY_QUEUE", "weather.processing.retry"),
            GetString("RABBITMQ_DLQ", "weather.processing.dlq"),
            GetString("RABBITMQ_ROUTING_KEY", "weather.snapshot.fetched"),
            GetInt("RABBITMQ_RETRY_TTL_MS", 5000),
            GetInt("RABBITMQ_MAX_RETRIES", 5)
        );
    }

    /// <summary>
    /// Cita string environment vrednost ili vraca default.
    /// </summary>
    private static string GetString(string name, string defaultValue)
    {
        var value = Environment.GetEnvironmentVariable(name);

        return string.IsNullOrWhiteSpace(value) ? defaultValue : value;
    }

    /// <summary>
    /// Cita int environment vrednost ili vraca default.
    /// </summary>
    private static int GetInt(string name, int defaultValue)
    {
        var value = Environment.GetEnvironmentVariable(name);

        return int.TryParse(value, out var parsedValue) ? parsedValue : defaultValue;
    }
}
