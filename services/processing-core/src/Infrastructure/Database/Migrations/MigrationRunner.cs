namespace Infrastructure.Database.Migrations;

using Infrastructure.Config;
using Npgsql;

/// <summary>
/// Pokrece SQL migracije za processing-core servis.
/// </summary>
public static class MigrationRunner
{
    /// <summary>
    /// Izvrsava idempotentne SQL migracije za osnovne processing tabele.
    /// </summary>
    public static async Task RunAsync()
    {
        var config = DatabaseConfig.FromEnvironment();
        var migrations = new[]
        {
            CreateConsumedEventsTableSql,
            CreateWeatherSnapshotsCurrentTableSql,
            CreateWeatherSnapshotsHistoryTableSql,
        };

        await using var connection = new NpgsqlConnection(config.ToConnectionString());
        await connection.OpenAsync();

        foreach (var migration in migrations)
        {
            await using var command = new NpgsqlCommand(migration, connection);
            await command.ExecuteNonQueryAsync();
        }

        Console.WriteLine("Processing-core migrations completed.");
    }

    private const string CreateConsumedEventsTableSql = """
        CREATE TABLE IF NOT EXISTS consumed_events (
            id BIGSERIAL PRIMARY KEY,
            event_id UUID NOT NULL UNIQUE,
            event_name VARCHAR(150) NOT NULL,
            producer VARCHAR(150) NOT NULL,
            consumed_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        );

        CREATE INDEX IF NOT EXISTS idx_consumed_events_event_name
            ON consumed_events (event_name);

        CREATE INDEX IF NOT EXISTS idx_consumed_events_consumed_at
            ON consumed_events (consumed_at);

        ALTER TABLE consumed_events
            ADD COLUMN IF NOT EXISTS producer VARCHAR(150);

        UPDATE consumed_events
            SET producer = 'unknown'
            WHERE producer IS NULL;

        ALTER TABLE consumed_events
            ALTER COLUMN producer SET NOT NULL;

        ALTER TABLE consumed_events
            ALTER COLUMN producer DROP DEFAULT;
        """;

    private const string CreateWeatherSnapshotsCurrentTableSql = """
        CREATE TABLE IF NOT EXISTS weather_snapshots_current (
            id BIGSERIAL PRIMARY KEY,
            event_id UUID NOT NULL UNIQUE,
            location_id UUID NOT NULL,
            city VARCHAR(120) NOT NULL,
            country VARCHAR(120) NOT NULL,
            latitude NUMERIC(10, 6) NOT NULL,
            longitude NUMERIC(10, 6) NOT NULL,
            temperature_c NUMERIC(8, 2) NOT NULL,
            wind_speed_kmh NUMERIC(8, 2) NOT NULL,
            weather_code INTEGER NOT NULL,
            observed_at TIMESTAMPTZ NOT NULL,
            source VARCHAR(80) NOT NULL,
            producer VARCHAR(150) NOT NULL,
            received_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            CONSTRAINT uq_weather_snapshots_current_location_source
                UNIQUE (location_id, source)
        );

        CREATE INDEX IF NOT EXISTS idx_weather_snapshots_current_location_observed_at
            ON weather_snapshots_current (location_id, observed_at);

        CREATE INDEX IF NOT EXISTS idx_weather_snapshots_current_source_observed_at
            ON weather_snapshots_current (source, observed_at);
        """;

    private const string CreateWeatherSnapshotsHistoryTableSql = """
        CREATE TABLE IF NOT EXISTS weather_snapshots_history (
            id BIGSERIAL PRIMARY KEY,
            event_id UUID NOT NULL UNIQUE,
            location_id UUID NOT NULL,
            city VARCHAR(120) NOT NULL,
            country VARCHAR(120) NOT NULL,
            latitude NUMERIC(10, 6) NOT NULL,
            longitude NUMERIC(10, 6) NOT NULL,
            temperature_c NUMERIC(8, 2) NOT NULL,
            wind_speed_kmh NUMERIC(8, 2) NOT NULL,
            weather_code INTEGER NOT NULL,
            observed_at TIMESTAMPTZ NOT NULL,
            source VARCHAR(80) NOT NULL,
            producer VARCHAR(150) NOT NULL,
            received_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        );

        CREATE INDEX IF NOT EXISTS idx_weather_snapshots_history_location_source_observed_at
            ON weather_snapshots_history (location_id, source, observed_at);

        CREATE INDEX IF NOT EXISTS idx_weather_snapshots_history_source_observed_at
            ON weather_snapshots_history (source, observed_at);

        CREATE INDEX IF NOT EXISTS idx_weather_snapshots_history_created_at
            ON weather_snapshots_history (created_at);
        """;
}
