namespace Infrastructure.Config;

/// <summary>
/// Cita konfiguraciju za zadrzavanje history weather podataka.
/// </summary>
public sealed class WeatherHistoryRetentionConfig
{
    public int RetentionDays { get; }
    public int CleanupIntervalHours { get; }

    private WeatherHistoryRetentionConfig(int retentionDays, int cleanupIntervalHours)
    {
        RetentionDays = retentionDays;
        CleanupIntervalHours = cleanupIntervalHours;
    }

    /// <summary>
    /// Kreira config objekat iz environment promenljivih.
    /// </summary>
    public static WeatherHistoryRetentionConfig FromEnvironment()
    {
        return new WeatherHistoryRetentionConfig(
            GetInt("WEATHER_HISTORY_RETENTION_DAYS", 90),
            GetInt("WEATHER_HISTORY_RETENTION_CLEANUP_INTERVAL_HOURS", 24)
        );
    }

    /// <summary>
    /// Cita int environment vrednost ili vraca default.
    /// </summary>
    private static int GetInt(string name, int defaultValue)
    {
        var value = Environment.GetEnvironmentVariable(name);

        return int.TryParse(value, out var parsedValue) && parsedValue > 0 ? parsedValue : defaultValue;
    }
}
