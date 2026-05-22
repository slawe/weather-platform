using Infrastructure.Config;
using Npgsql;

namespace Infrastructure.Weather;

/// <summary>
/// Brise zastarele history zapise bez diranja current read modela.
/// </summary>
public sealed class WeatherHistoryRetentionCleanup
{
    private readonly DatabaseConfig databaseConfig;
    private readonly WeatherHistoryRetentionConfig retentionConfig;

    public WeatherHistoryRetentionCleanup(
        DatabaseConfig databaseConfig,
        WeatherHistoryRetentionConfig retentionConfig
    )
    {
        this.databaseConfig = databaseConfig;
        this.retentionConfig = retentionConfig;
    }

    public WeatherHistoryRetentionCleanup(DatabaseConfig databaseConfig)
        : this(databaseConfig, WeatherHistoryRetentionConfig.FromEnvironment())
    {
    }

    /// <summary>
    /// Brise history zapise starije od konfigurisanog retention perioda.
    /// </summary>
    public async Task<int> DeleteExpiredHistoryAsync()
    {
        await using var connection = new NpgsqlConnection(databaseConfig.ToConnectionString());
        await connection.OpenAsync();

        await using var command = connection.CreateCommand();
        command.CommandText = """
            DELETE FROM weather_snapshots_history
            WHERE observed_at < (NOW() AT TIME ZONE 'UTC') - (@retentionDays * INTERVAL '1 day');
            """;
        command.Parameters.AddWithValue("retentionDays", retentionConfig.RetentionDays);

        return await command.ExecuteNonQueryAsync();
    }
}
