using Application.Messaging.DTO;
using Application.Weather;
using Npgsql;

namespace Infrastructure.Weather;

/// <summary>
/// Upisuje current i history weather snapshot read modele.
/// </summary>
public sealed class WeatherSnapshotWriteRepository
{
    /// <summary>
    /// Upsertuje poslednje poznato stanje po location_id + source.
    /// </summary>
    public async Task UpsertCurrentSnapshotAsync(
        NpgsqlConnection connection,
        NpgsqlTransaction transaction,
        WeatherSnapshotFetchedEnvelope envelope
    )
    {
        const string sql = """
            INSERT INTO weather_snapshots_current (
                event_id,
                location_id,
                city,
                country,
                latitude,
                longitude,
                temperature_c,
                wind_speed_kmh,
                weather_code,
                observed_at,
                source,
                producer,
                received_at,
                created_at,
                updated_at
            )
            VALUES (
                @event_id,
                @location_id,
                @city,
                @country,
                @latitude,
                @longitude,
                @temperature_c,
                @wind_speed_kmh,
                @weather_code,
                @observed_at,
                @source,
                @producer,
                NOW(),
                NOW(),
                NOW()
            )
            ON CONFLICT (location_id, source) DO UPDATE SET
                event_id = EXCLUDED.event_id,
                city = EXCLUDED.city,
                country = EXCLUDED.country,
                latitude = EXCLUDED.latitude,
                longitude = EXCLUDED.longitude,
                temperature_c = EXCLUDED.temperature_c,
                wind_speed_kmh = EXCLUDED.wind_speed_kmh,
                weather_code = EXCLUDED.weather_code,
                observed_at = EXCLUDED.observed_at,
                producer = EXCLUDED.producer,
                received_at = EXCLUDED.received_at,
                updated_at = NOW();
            """;

        await using var command = new NpgsqlCommand(sql, connection, transaction);
        AddSnapshotParameters(command, envelope);

        await command.ExecuteNonQueryAsync();
    }

    /// <summary>
    /// Ucitava poslednji history zapis za isti location_id + source.
    /// </summary>
    public async Task<LastWeatherHistorySnapshot?> GetLastHistorySnapshotAsync(
        NpgsqlConnection connection,
        NpgsqlTransaction transaction,
        WeatherSnapshotPayload payload
    )
    {
        const string sql = """
            SELECT
                temperature_c,
                wind_speed_kmh,
                weather_code,
                observed_at
            FROM weather_snapshots_history
            WHERE location_id = @location_id
              AND source = @source
            ORDER BY observed_at DESC, id DESC
            LIMIT 1;
            """;

        await using var command = new NpgsqlCommand(sql, connection, transaction);
        command.Parameters.AddWithValue("location_id", payload.LocationId);
        command.Parameters.AddWithValue("source", payload.Source);

        await using var reader = await command.ExecuteReaderAsync();

        if (!await reader.ReadAsync())
        {
            return null;
        }

        return new LastWeatherHistorySnapshot(
            reader.GetDecimal(0),
            reader.GetDecimal(1),
            reader.GetInt32(2),
            reader.GetFieldValue<DateTimeOffset>(3)
        );
    }

    /// <summary>
    /// Upisuje novi history zapis za znacajnu promenu.
    /// </summary>
    public async Task InsertHistorySnapshotAsync(
        NpgsqlConnection connection,
        NpgsqlTransaction transaction,
        WeatherSnapshotFetchedEnvelope envelope
    )
    {
        const string sql = """
            INSERT INTO weather_snapshots_history (
                event_id,
                location_id,
                city,
                country,
                latitude,
                longitude,
                temperature_c,
                wind_speed_kmh,
                weather_code,
                observed_at,
                source,
                producer,
                received_at,
                created_at
            )
            VALUES (
                @event_id,
                @location_id,
                @city,
                @country,
                @latitude,
                @longitude,
                @temperature_c,
                @wind_speed_kmh,
                @weather_code,
                @observed_at,
                @source,
                @producer,
                NOW(),
                NOW()
            );
            """;

        await using var command = new NpgsqlCommand(sql, connection, transaction);
        AddSnapshotParameters(command, envelope);

        await command.ExecuteNonQueryAsync();
    }

    /// <summary>
    /// Dodaje standardne snapshot parametre na SQL komandu.
    /// </summary>
    private static void AddSnapshotParameters(
        NpgsqlCommand command,
        WeatherSnapshotFetchedEnvelope envelope
    )
    {
        var payload = envelope.Payload!;

        command.Parameters.AddWithValue("event_id", envelope.EventId);
        command.Parameters.AddWithValue("location_id", payload.LocationId);
        command.Parameters.AddWithValue("city", payload.City);
        command.Parameters.AddWithValue("country", payload.Country);
        command.Parameters.AddWithValue("latitude", payload.Latitude);
        command.Parameters.AddWithValue("longitude", payload.Longitude);
        command.Parameters.AddWithValue("temperature_c", payload.TemperatureC);
        command.Parameters.AddWithValue("wind_speed_kmh", payload.WindSpeedKmh);
        command.Parameters.AddWithValue("weather_code", payload.WeatherCode);
        command.Parameters.AddWithValue("observed_at", payload.ObservedAt);
        command.Parameters.AddWithValue("source", payload.Source);
        command.Parameters.AddWithValue("producer", envelope.Producer);
    }
}
