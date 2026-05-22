using Application.Messaging.DTO;
using Infrastructure.Config;
using Infrastructure.Weather;
using Npgsql;
using Xunit;

namespace ProcessingCore.Tests.Weather;

/// <summary>
/// Integration testovi za idempotency, current upsert i controlled history pravila.
/// </summary>
public sealed class WeatherSnapshotFetchedProcessorTests : IAsyncLifetime
{
    private readonly DatabaseConfig databaseConfig = DatabaseConfig.FromEnvironment();
    private readonly Guid locationId = Guid.NewGuid();
    private readonly string source;

    public WeatherSnapshotFetchedProcessorTests()
    {
        source = $"test-source-{Guid.NewGuid():N}";
    }

    /// <summary>
    /// Cisti eventualne stare test podatke pre testa.
    /// </summary>
    public Task InitializeAsync()
    {
        return CleanupAsync();
    }

    /// <summary>
    /// Cisti test podatke posle testa.
    /// </summary>
    public Task DisposeAsync()
    {
        return CleanupAsync();
    }

    /// <summary>
    /// Proverava da isti event_id ne pravi drugi side effect.
    /// </summary>
    [Fact]
    public async Task ProcessAsync_ShouldIgnoreDuplicateEventId()
    {
        var processor = new WeatherSnapshotFetchedProcessor(databaseConfig);
        var eventId = Guid.NewGuid();
        var envelope = CreateEnvelope(eventId, 22.5m, 6.1m, 2, DateTimeOffset.Parse("2026-05-21T10:00:00Z"));

        var firstResult = await processor.ProcessAsync(envelope);
        var secondResult = await processor.ProcessAsync(envelope);

        Assert.True(firstResult);
        Assert.False(secondResult);
        Assert.Equal(1, await CountCurrentRowsAsync());
        Assert.Equal(1, await CountHistoryRowsAsync());
    }

    /// <summary>
    /// Proverava da novi event za isti location_id + source azurira current red.
    /// </summary>
    [Fact]
    public async Task ProcessAsync_ShouldUpsertCurrentSnapshotByLocationAndSource()
    {
        var processor = new WeatherSnapshotFetchedProcessor(databaseConfig);

        await processor.ProcessAsync(CreateEnvelope(Guid.NewGuid(), 22.5m, 6.1m, 2, DateTimeOffset.Parse("2026-05-21T10:00:00Z")));
        await processor.ProcessAsync(CreateEnvelope(Guid.NewGuid(), 24.2m, 6.1m, 2, DateTimeOffset.Parse("2026-05-21T10:10:00Z")));

        var currentTemperature = await ReadCurrentTemperatureAsync();

        Assert.Equal(1, await CountCurrentRowsAsync());
        Assert.Equal(24.2m, currentTemperature);
    }

    /// <summary>
    /// Proverava da history ne cuva raw log kada nema znacajne promene.
    /// </summary>
    [Fact]
    public async Task ProcessAsync_ShouldNotInsertHistoryWhenValuesDoNotChangeOnSameDay()
    {
        var processor = new WeatherSnapshotFetchedProcessor(databaseConfig);

        await processor.ProcessAsync(CreateEnvelope(Guid.NewGuid(), 22.5m, 6.1m, 2, DateTimeOffset.Parse("2026-05-21T10:00:00Z")));
        await processor.ProcessAsync(CreateEnvelope(Guid.NewGuid(), 22.5m, 6.1m, 2, DateTimeOffset.Parse("2026-05-21T10:05:00Z")));

        Assert.Equal(1, await CountCurrentRowsAsync());
        Assert.Equal(1, await CountHistoryRowsAsync());
    }

    /// <summary>
    /// Proverava da history dobija novi red kada postoji znacajna promena.
    /// </summary>
    [Fact]
    public async Task ProcessAsync_ShouldInsertHistoryWhenSignificantChangeExists()
    {
        var processor = new WeatherSnapshotFetchedProcessor(databaseConfig);

        await processor.ProcessAsync(CreateEnvelope(Guid.NewGuid(), 22.5m, 6.1m, 2, DateTimeOffset.Parse("2026-05-21T10:00:00Z")));
        await processor.ProcessAsync(CreateEnvelope(Guid.NewGuid(), 23.0m, 6.1m, 2, DateTimeOffset.Parse("2026-05-21T10:10:00Z")));
        await processor.ProcessAsync(CreateEnvelope(Guid.NewGuid(), 23.0m, 6.1m, 2, DateTimeOffset.Parse("2026-05-22T00:05:00Z")));

        Assert.Equal(1, await CountCurrentRowsAsync());
        Assert.Equal(3, await CountHistoryRowsAsync());
    }

    /// <summary>
    /// Kreira canonical envelope za processor testove.
    /// </summary>
    private WeatherSnapshotFetchedEnvelope CreateEnvelope(
        Guid eventId,
        decimal temperatureC,
        decimal windSpeedKmh,
        int weatherCode,
        DateTimeOffset observedAt
    )
    {
        return new WeatherSnapshotFetchedEnvelope
        {
            EventId = eventId,
            EventName = "weather.snapshot.fetched",
            EventVersion = 1,
            OccurredAt = observedAt,
            Producer = "processing-core-tests",
            Payload = new WeatherSnapshotPayload
            {
                LocationId = locationId,
                City = "Test City",
                Country = "Test Country",
                Latitude = 44.8178m,
                Longitude = 20.4569m,
                TemperatureC = temperatureC,
                WindSpeedKmh = windSpeedKmh,
                WeatherCode = weatherCode,
                ObservedAt = observedAt,
                Source = source,
            },
        };
    }

    /// <summary>
    /// Brise samo redove koje je kreirao ovaj test.
    /// </summary>
    private async Task CleanupAsync()
    {
        await using var connection = new NpgsqlConnection(databaseConfig.ToConnectionString());
        await connection.OpenAsync();

        await using var command = connection.CreateCommand();
        command.CommandText = """
            DELETE FROM weather_snapshots_current WHERE location_id = @location_id AND source = @source;
            DELETE FROM weather_snapshots_history WHERE location_id = @location_id AND source = @source;
            DELETE FROM consumed_events WHERE producer = 'processing-core-tests';
            """;
        command.Parameters.AddWithValue("location_id", locationId);
        command.Parameters.AddWithValue("source", source);

        await command.ExecuteNonQueryAsync();
    }

    /// <summary>
    /// Broji current redove za test lokaciju i source.
    /// </summary>
    private Task<int> CountCurrentRowsAsync()
    {
        return ExecuteScalarIntAsync("SELECT COUNT(*) FROM weather_snapshots_current WHERE location_id = @location_id AND source = @source;");
    }

    /// <summary>
    /// Broji history redove za test lokaciju i source.
    /// </summary>
    private Task<int> CountHistoryRowsAsync()
    {
        return ExecuteScalarIntAsync("SELECT COUNT(*) FROM weather_snapshots_history WHERE location_id = @location_id AND source = @source;");
    }

    /// <summary>
    /// Cita current temperaturu za test lokaciju i source.
    /// </summary>
    private async Task<decimal> ReadCurrentTemperatureAsync()
    {
        await using var connection = new NpgsqlConnection(databaseConfig.ToConnectionString());
        await connection.OpenAsync();

        await using var command = connection.CreateCommand();
        command.CommandText = "SELECT temperature_c FROM weather_snapshots_current WHERE location_id = @location_id AND source = @source;";
        command.Parameters.AddWithValue("location_id", locationId);
        command.Parameters.AddWithValue("source", source);

        var result = await command.ExecuteScalarAsync();

        return (decimal)(result ?? throw new InvalidOperationException("Current snapshot nije pronadjen."));
    }

    /// <summary>
    /// Izvrsava COUNT query za test lokaciju i source.
    /// </summary>
    private async Task<int> ExecuteScalarIntAsync(string sql)
    {
        await using var connection = new NpgsqlConnection(databaseConfig.ToConnectionString());
        await connection.OpenAsync();

        await using var command = connection.CreateCommand();
        command.CommandText = sql;
        command.Parameters.AddWithValue("location_id", locationId);
        command.Parameters.AddWithValue("source", source);

        var result = await command.ExecuteScalarAsync();

        return Convert.ToInt32(result);
    }
}
