namespace Infrastructure.Weather.ReadModel;

/// <summary>
/// Read model za trenutno stanje vremenskog snapshot-a.
/// </summary>
public sealed record CurrentWeatherSnapshot(
    Guid EventId,
    Guid LocationId,
    string City,
    string Country,
    decimal Latitude,
    decimal Longitude,
    decimal TemperatureC,
    decimal WindSpeedKmh,
    int WeatherCode,
    DateTimeOffset ObservedAt,
    string Source,
    string Producer,
    DateTimeOffset ReceivedAt
);
