using System.Text.Json.Serialization;
using Application.Messaging.Serialization;

namespace Application.Messaging.DTO;

/// <summary>
/// Canonical integration envelope koji producer servisi salju preko RabbitMQ-a.
/// </summary>
public sealed class WeatherSnapshotFetchedEnvelope
{
    [JsonPropertyName("event_id")]
    public Guid EventId { get; init; }

    [JsonPropertyName("event_name")]
    public string EventName { get; init; } = string.Empty;

    [JsonPropertyName("event_version")]
    public int EventVersion { get; init; }

    [JsonPropertyName("occurred_at")]
    [JsonConverter(typeof(FlexibleDateTimeOffsetJsonConverter))]
    public DateTimeOffset OccurredAt { get; init; }

    [JsonPropertyName("producer")]
    public string Producer { get; init; } = string.Empty;

    [JsonPropertyName("payload")]
    public WeatherSnapshotPayload? Payload { get; init; }
}
