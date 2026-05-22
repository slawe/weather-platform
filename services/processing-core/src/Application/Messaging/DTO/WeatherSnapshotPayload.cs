using System.Text.Json.Serialization;
using Application.Messaging.Serialization;

namespace Application.Messaging.DTO;

/// <summary>
/// Canonical payload za weather.snapshot.fetched event.
/// </summary>
public sealed class WeatherSnapshotPayload
{
    [JsonPropertyName("location_id")]
    public Guid LocationId { get; init; }

    [JsonPropertyName("city")]
    public string City { get; init; } = string.Empty;

    [JsonPropertyName("country")]
    public string Country { get; init; } = string.Empty;

    [JsonPropertyName("latitude")]
    public decimal Latitude { get; init; }

    [JsonPropertyName("longitude")]
    public decimal Longitude { get; init; }

    [JsonPropertyName("temperature_c")]
    public decimal TemperatureC { get; init; }

    [JsonPropertyName("wind_speed_kmh")]
    public decimal WindSpeedKmh { get; init; }

    [JsonPropertyName("weather_code")]
    public int WeatherCode { get; init; }

    [JsonPropertyName("observed_at")]
    [JsonConverter(typeof(FlexibleDateTimeOffsetJsonConverter))]
    public DateTimeOffset ObservedAt { get; init; }

    [JsonPropertyName("source")]
    public string Source { get; init; } = string.Empty;
}
