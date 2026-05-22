using Infrastructure.Config;
using RabbitMQ.Client;

namespace Infrastructure.Messaging.RabbitMq;

/// <summary>
/// Pravi RabbitMQ konekcije na osnovu konfiguracije servisa.
/// </summary>
public sealed class RabbitMqConnectionFactory
{
    private readonly RabbitMqConfig config;

    public RabbitMqConnectionFactory(RabbitMqConfig config)
    {
        this.config = config;
    }

    /// <summary>
    /// Otvara novu RabbitMQ konekciju.
    /// </summary>
    public IConnection CreateConnection()
    {
        var factory = new ConnectionFactory
        {
            HostName = config.Host,
            Port = config.Port,
            UserName = config.User,
            Password = config.Password,
            VirtualHost = config.VHost,
            DispatchConsumersAsync = false,
        };

        return factory.CreateConnection();
    }
}
