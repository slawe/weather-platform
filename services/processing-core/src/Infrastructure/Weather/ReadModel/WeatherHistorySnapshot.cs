namespace Infrastructure.Weather.ReadModel;

/// <summary>
/// Read model za history weather snapshot zapis.
/// </summary>
public sealed record WeatherHistorySnapshot(
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
