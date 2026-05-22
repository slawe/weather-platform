using Infrastructure.Config;
using Infrastructure.Weather;

namespace Api.BackgroundServices;

/// <summary>
/// Periodično pokreće cleanup zastarelih history zapisa.
/// </summary>
public sealed class WeatherHistoryRetentionHostedService : BackgroundService
{
    private readonly WeatherHistoryRetentionCleanup cleanup;
    private readonly WeatherHistoryRetentionConfig config;
    private readonly ILogger<WeatherHistoryRetentionHostedService> logger;

    public WeatherHistoryRetentionHostedService(
        WeatherHistoryRetentionCleanup cleanup,
        WeatherHistoryRetentionConfig config,
        ILogger<WeatherHistoryRetentionHostedService> logger
    )
    {
        this.cleanup = cleanup;
        this.config = config;
        this.logger = logger;
    }

    /// <summary>
    /// Pokreće cleanup na startu servisa i zatim ga ponavlja u konfigurisanom intervalu.
    /// </summary>
    protected override async Task ExecuteAsync(CancellationToken stoppingToken)
    {
        var interval = TimeSpan.FromHours(config.CleanupIntervalHours);

        while (!stoppingToken.IsCancellationRequested)
        {
            await RunCleanupAsync(stoppingToken);
            await Task.Delay(interval, stoppingToken);
        }
    }

    /// <summary>
    /// Izvršava jedan cleanup ciklus i loguje rezultat bez rušenja API procesa.
    /// </summary>
    private async Task RunCleanupAsync(CancellationToken stoppingToken)
    {
        try
        {
            var deletedRows = await cleanup.DeleteExpiredHistoryAsync();

            logger.LogInformation(
                "History retention cleanup zavrsen. Deleted rows: {DeletedRows}.",
                deletedRows
            );
        }
        catch (OperationCanceledException) when (stoppingToken.IsCancellationRequested)
        {
        }
        catch (Exception exception)
        {
            logger.LogWarning(exception, "History retention cleanup nije uspeo.");
        }
    }
}
