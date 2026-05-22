using Application.Messaging.DTO;
using Npgsql;

namespace Infrastructure.Weather;

/// <summary>
/// Upisuje tehnicke consumed event zapise za idempotency proveru.
/// </summary>
public sealed class ConsumedEventRepository
{
    /// <summary>
    /// Pokusava da upise event u consumed_events i vraca false ako event vec postoji.
    /// </summary>
    public async Task<bool> TryMarkAsConsumedAsync(
        NpgsqlConnection connection,
        NpgsqlTransaction transaction,
        WeatherSnapshotFetchedEnvelope envelope
    )
    {
        const string sql = """
            INSERT INTO consumed_events (
                event_id,
                event_name,
                producer,
                consumed_at,
                created_at
            )
            VALUES (
                @event_id,
                @event_name,
                @producer,
                NOW(),
                NOW()
            )
            ON CONFLICT (event_id) DO NOTHING
            RETURNING id;
            """;

        await using var command = new NpgsqlCommand(sql, connection, transaction);
        command.Parameters.AddWithValue("event_id", envelope.EventId);
        command.Parameters.AddWithValue("event_name", envelope.EventName);
        command.Parameters.AddWithValue("producer", envelope.Producer);

        var result = await command.ExecuteScalarAsync();

        return result is not null;
    }
}
