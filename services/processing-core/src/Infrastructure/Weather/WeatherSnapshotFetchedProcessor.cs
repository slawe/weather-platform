using Application.Messaging.DTO;
using Application.Weather;
using Infrastructure.Config;
using Npgsql;

namespace Infrastructure.Weather;

/// <summary>
/// Koordinira idempotentnu obradu weather.snapshot.fetched eventa kroz jednu transakciju.
/// </summary>
public sealed class WeatherSnapshotFetchedProcessor
{
    private readonly DatabaseConfig databaseConfig;
    private readonly ConsumedEventRepository consumedEventRepository;
    private readonly WeatherSnapshotWriteRepository weatherSnapshotWriteRepository;
    private readonly SignificantWeatherChangeDetector changeDetector;

    public WeatherSnapshotFetchedProcessor(DatabaseConfig databaseConfig)
        : this(
            databaseConfig,
            new ConsumedEventRepository(),
            new WeatherSnapshotWriteRepository(),
            new SignificantWeatherChangeDetector()
        )
    {
    }

    public WeatherSnapshotFetchedProcessor(
        DatabaseConfig databaseConfig,
        ConsumedEventRepository consumedEventRepository,
        WeatherSnapshotWriteRepository weatherSnapshotWriteRepository,
        SignificantWeatherChangeDetector changeDetector
    )
    {
        this.databaseConfig = databaseConfig;
        this.consumedEventRepository = consumedEventRepository;
        this.weatherSnapshotWriteRepository = weatherSnapshotWriteRepository;
        this.changeDetector = changeDetector;
    }

    /// <summary>
    /// Idempotentno obradjuje event i vraca false ako je event vec obradjen.
    /// </summary>
    public async Task<bool> ProcessAsync(WeatherSnapshotFetchedEnvelope envelope)
    {
        if (envelope.Payload is null)
        {
            throw new InvalidOperationException("Payload je obavezan za obradu eventa.");
        }

        await using var connection = new NpgsqlConnection(databaseConfig.ToConnectionString());
        await connection.OpenAsync();
        await using var transaction = await connection.BeginTransactionAsync();

        var shouldProcess = await consumedEventRepository.TryMarkAsConsumedAsync(connection, transaction, envelope);

        if (!shouldProcess)
        {
            await transaction.RollbackAsync();

            return false;
        }

        await weatherSnapshotWriteRepository.UpsertCurrentSnapshotAsync(connection, transaction, envelope);

        var lastHistory = await weatherSnapshotWriteRepository.GetLastHistorySnapshotAsync(
            connection,
            transaction,
            envelope.Payload
        );

        if (changeDetector.HasSignificantChange(envelope.Payload, lastHistory))
        {
            await weatherSnapshotWriteRepository.InsertHistorySnapshotAsync(connection, transaction, envelope);
        }

        await transaction.CommitAsync();

        return true;
    }
}
