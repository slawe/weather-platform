using Infrastructure.Config;
using Npgsql;

namespace Infrastructure.Weather.ReadModel;

/// <summary>
/// Čita current, history i average weather podatke iz Postgres read modela.
/// </summary>
public sealed class WeatherReadRepository
{
    private readonly DatabaseConfig databaseConfig;

    public WeatherReadRepository(DatabaseConfig databaseConfig)
    {
        this.databaseConfig = databaseConfig;
    }

    /// <summary>
    /// Vraća sve current weather snapshot-e.
    /// </summary>
    public async Task<IReadOnlyList<CurrentWeatherSnapshot>> GetCurrentAsync()
    {
        const string sql = """
            SELECT
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
                received_at
            FROM weather_snapshots_current
            ORDER BY city, source;
            """;

        await using var connection = new NpgsqlConnection(databaseConfig.ToConnectionString());
        await connection.OpenAsync();
        await using var command = new NpgsqlCommand(sql, connection);
        await using var reader = await command.ExecuteReaderAsync();

        var snapshots = new List<CurrentWeatherSnapshot>();

        while (await reader.ReadAsync())
        {
            snapshots.Add(ReadCurrentSnapshot(reader));
        }

        return snapshots;
    }

    /// <summary>
    /// Vraća history snapshot-e filtrirane po query parametrima.
    /// </summary>
    public async Task<IReadOnlyList<WeatherHistorySnapshot>> GetHistoryAsync(
        Guid? locationId,
        string? source,
        DateTimeOffset? from,
        DateTimeOffset? to
    )
    {
        var (whereClause, parameters) = BuildHistoryFilters(locationId, source, from, to);
        var sql = $"""
            SELECT
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
                received_at
            FROM weather_snapshots_history
            {whereClause}
            ORDER BY observed_at, city, source;
            """;

        await using var connection = new NpgsqlConnection(databaseConfig.ToConnectionString());
        await connection.OpenAsync();
        await using var command = new NpgsqlCommand(sql, connection);
        AddParameters(command, parameters);
        await using var reader = await command.ExecuteReaderAsync();

        var snapshots = new List<WeatherHistorySnapshot>();

        while (await reader.ReadAsync())
        {
            snapshots.Add(ReadHistorySnapshot(reader));
        }

        return snapshots;
    }

    /// <summary>
    /// Računa nedeljne proseke temperature iz history tabele.
    /// </summary>
    public Task<IReadOnlyList<WeatherAverage>> GetWeeklyAveragesAsync(
        Guid? locationId,
        string? source,
        DateTimeOffset? from,
        DateTimeOffset? to
    )
    {
        return GetAveragesAsync("week", locationId, source, from, to);
    }

    /// <summary>
    /// Računa mesečne proseke temperature iz history tabele.
    /// </summary>
    public Task<IReadOnlyList<WeatherAverage>> GetMonthlyAveragesAsync(
        Guid? locationId,
        string? source,
        DateTimeOffset? from,
        DateTimeOffset? to
    )
    {
        return GetAveragesAsync("month", locationId, source, from, to);
    }

    /// <summary>
    /// Računa tromesečne proseke temperature iz history tabele.
    /// </summary>
    public Task<IReadOnlyList<WeatherAverage>> GetQuarterlyAveragesAsync(
        Guid? locationId,
        string? source,
        DateTimeOffset? from,
        DateTimeOffset? to
    )
    {
        return GetAveragesAsync("quarter", locationId, source, from, to);
    }

    /// <summary>
    /// Računa proseke temperature za zadati date_trunc period.
    /// </summary>
    private async Task<IReadOnlyList<WeatherAverage>> GetAveragesAsync(
        string period,
        Guid? locationId,
        string? source,
        DateTimeOffset? from,
        DateTimeOffset? to
    )
    {
        var (whereClause, parameters) = BuildHistoryFilters(locationId, source, from, to);
        var sql = $"""
            SELECT
                location_id,
                source,
                date_trunc('{period}', observed_at) AS period_start,
                AVG(temperature_c)::numeric(8, 2) AS average_temperature_c
            FROM weather_snapshots_history
            {whereClause}
            GROUP BY location_id, source, period_start
            ORDER BY period_start, location_id, source;
            """;

        await using var connection = new NpgsqlConnection(databaseConfig.ToConnectionString());
        await connection.OpenAsync();
        await using var command = new NpgsqlCommand(sql, connection);
        AddParameters(command, parameters);
        await using var reader = await command.ExecuteReaderAsync();

        var averages = new List<WeatherAverage>();

        while (await reader.ReadAsync())
        {
            averages.Add(new WeatherAverage(
                reader.GetGuid(0),
                reader.GetString(1),
                reader.GetFieldValue<DateTimeOffset>(2),
                reader.GetDecimal(3)
            ));
        }

        return averages;
    }

    /// <summary>
    /// Pravi WHERE deo SQL upita na osnovu history filtera.
    /// </summary>
    private static (string WhereClause, Dictionary<string, object> Parameters) BuildHistoryFilters(
        Guid? locationId,
        string? source,
        DateTimeOffset? from,
        DateTimeOffset? to
    )
    {
        var conditions = new List<string>();
        var parameters = new Dictionary<string, object>();

        if (locationId.HasValue)
        {
            conditions.Add("location_id = @location_id");
            parameters["location_id"] = locationId.Value;
        }

        if (!string.IsNullOrWhiteSpace(source))
        {
            conditions.Add("source = @source");
            parameters["source"] = source;
        }

        if (from.HasValue)
        {
            conditions.Add("observed_at >= @from");
            parameters["from"] = from.Value;
        }

        if (to.HasValue)
        {
            conditions.Add("observed_at <= @to");
            parameters["to"] = to.Value;
        }

        return conditions.Count == 0
            ? (string.Empty, parameters)
            : ($"WHERE {string.Join(" AND ", conditions)}", parameters);
    }

    /// <summary>
    /// Dodaje parametre u Npgsql komandu.
    /// </summary>
    private static void AddParameters(NpgsqlCommand command, Dictionary<string, object> parameters)
    {
        foreach (var parameter in parameters)
        {
            command.Parameters.AddWithValue(parameter.Key, parameter.Value);
        }
    }

    /// <summary>
    /// Mapira red iz current tabele u read model.
    /// </summary>
    private static CurrentWeatherSnapshot ReadCurrentSnapshot(NpgsqlDataReader reader)
    {
        return new CurrentWeatherSnapshot(
            reader.GetGuid(0),
            reader.GetGuid(1),
            reader.GetString(2),
            reader.GetString(3),
            reader.GetDecimal(4),
            reader.GetDecimal(5),
            reader.GetDecimal(6),
            reader.GetDecimal(7),
            reader.GetInt32(8),
            reader.GetFieldValue<DateTimeOffset>(9),
            reader.GetString(10),
            reader.GetString(11),
            reader.GetFieldValue<DateTimeOffset>(12)
        );
    }

    /// <summary>
    /// Mapira red iz history tabele u read model.
    /// </summary>
    private static WeatherHistorySnapshot ReadHistorySnapshot(NpgsqlDataReader reader)
    {
        return new WeatherHistorySnapshot(
            reader.GetGuid(0),
            reader.GetGuid(1),
            reader.GetString(2),
            reader.GetString(3),
            reader.GetDecimal(4),
            reader.GetDecimal(5),
            reader.GetDecimal(6),
            reader.GetDecimal(7),
            reader.GetInt32(8),
            reader.GetFieldValue<DateTimeOffset>(9),
            reader.GetString(10),
            reader.GetString(11),
            reader.GetFieldValue<DateTimeOffset>(12)
        );
    }
}
