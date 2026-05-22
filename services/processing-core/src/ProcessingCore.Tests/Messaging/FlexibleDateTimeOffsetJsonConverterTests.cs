using System.Text.Json;
using Application.Messaging.DTO;
using Xunit;

namespace ProcessingCore.Tests.Messaging;

/// <summary>
/// Testovi za timestamp formate koje salju producer servisi.
/// </summary>
public sealed class FlexibleDateTimeOffsetJsonConverterTests
{
    /// <summary>
    /// Proverava da ISO/RFC3339 timestamp ostaje podrzan.
    /// </summary>
    [Fact]
    public void Deserialize_ShouldReadIsoTimestamp()
    {
        var envelope = JsonSerializer.Deserialize<WeatherSnapshotFetchedEnvelope>(JsonBody("2026-05-21T15:00:00Z"));

        Assert.NotNull(envelope?.Payload);
        Assert.Equal(DateTimeOffset.Parse("2026-05-21T15:00:00Z"), envelope.Payload.ObservedAt);
    }

    /// <summary>
    /// Proverava provider timestamp bez timezone offset-a koji salje WeatherAPI producer.
    /// </summary>
    [Fact]
    public void Deserialize_ShouldReadProviderTimestampWithoutOffsetAsUtc()
    {
        var envelope = JsonSerializer.Deserialize<WeatherSnapshotFetchedEnvelope>(JsonBody("2026-05-21 15:00"));

        Assert.NotNull(envelope?.Payload);
        Assert.Equal(DateTimeOffset.Parse("2026-05-21T15:00:00Z"), envelope.Payload.ObservedAt);
    }

    /// <summary>
    /// Kreira minimalan canonical JSON body za test deserializacije.
    /// </summary>
    private static string JsonBody(string observedAt)
    {
        return $$"""
            {
              "event_id": "11111111-1111-4111-8111-111111111111",
              "event_name": "weather.snapshot.fetched",
              "event_version": 1,
              "occurred_at": "2026-05-21T13:00:00Z",
              "producer": "test-producer",
              "payload": {
                "location_id": "22222222-2222-4222-8222-222222222222",
                "city": "Belgrade",
                "country": "Serbia",
                "latitude": 44.8178,
                "longitude": 20.4569,
                "temperature_c": 22.5,
                "wind_speed_kmh": 6.1,
                "weather_code": 2,
                "observed_at": "{{observedAt}}",
                "source": "test-source"
              }
            }
            """;
    }
}
