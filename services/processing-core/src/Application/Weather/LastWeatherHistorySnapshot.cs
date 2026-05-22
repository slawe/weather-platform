namespace Application.Weather;

/// <summary>
/// Minimalan model poslednjeg history zapisa potreban za significant-change pravilo.
/// </summary>
public sealed record LastWeatherHistorySnapshot(
    decimal TemperatureC,
    decimal WindSpeedKmh,
    int WeatherCode,
    DateTimeOffset ObservedAt
);
