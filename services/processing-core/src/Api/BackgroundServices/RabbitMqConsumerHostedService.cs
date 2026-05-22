using Infrastructure.Messaging.RabbitMq;
using Microsoft.Extensions.Hosting;
using Microsoft.Extensions.Logging;

namespace Api.BackgroundServices;

/// <summary>
/// Hosted service koji pokrece RabbitMQ consumer zajedno sa ASP.NET Core aplikacijom.
/// </summary>
public sealed class RabbitMqConsumerHostedService : BackgroundService
{
    private static readonly TimeSpan RetryDelay = TimeSpan.FromSeconds(5);

    private readonly RabbitMqWeatherConsumer consumer;
    private readonly ILogger<RabbitMqConsumerHostedService> logger;

    public RabbitMqConsumerHostedService(
        RabbitMqWeatherConsumer consumer,
        ILogger<RabbitMqConsumerHostedService> logger
    )
    {
        this.consumer = consumer;
        this.logger = logger;
    }

    /// <summary>
    /// Pokrece RabbitMQ consumer i ponavlja pokusaj ako broker nije spreman ili konekcija pukne.
    /// </summary>
    protected override async Task ExecuteAsync(CancellationToken stoppingToken)
    {
        while (!stoppingToken.IsCancellationRequested)
        {
            try
            {
                await Task.Run(() => consumer.Consume(stoppingToken), stoppingToken);
            }
            catch (OperationCanceledException) when (stoppingToken.IsCancellationRequested)
            {
                return;
            }
            catch (Exception exception)
            {
                logger.LogWarning(
                    exception,
                    "RabbitMQ consumer nije pokrenut. Novi pokusaj za {RetryDelaySeconds} sekundi.",
                    RetryDelay.TotalSeconds
                );

                await Task.Delay(RetryDelay, stoppingToken);
            }
        }
    }
}
