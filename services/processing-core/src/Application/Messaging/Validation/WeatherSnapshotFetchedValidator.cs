using Application.Messaging.DTO;

namespace Application.Messaging.Validation;

/// <summary>
/// Validira canonical weather.snapshot.fetched envelope pre obrade.
/// </summary>
public static class WeatherSnapshotFetchedValidator
{
    private const string ExpectedEventName = "weather.snapshot.fetched";
    private const int ExpectedEventVersion = 1;

    /// <summary>
    /// Baca exception ako envelope nema obavezne ili ocekivane vrednosti.
    /// </summary>
    public static void Validate(WeatherSnapshotFetchedEnvelope envelope)
    {
        if (envelope.EventId == Guid.Empty)
        {
            throw new InvalidOperationException("Event id je obavezan.");
        }

        if (envelope.EventName != ExpectedEventName)
        {
            throw new InvalidOperationException($"Nepodrzan event name: {envelope.EventName}");
        }

        if (envelope.EventVersion != ExpectedEventVersion)
        {
            throw new InvalidOperationException($"Nepodrzana event verzija: {envelope.EventVersion}");
        }

        if (string.IsNullOrWhiteSpace(envelope.Producer))
        {
            throw new InvalidOperationException("Producer je obavezan.");
        }

        if (envelope.Payload is null)
        {
            throw new InvalidOperationException("Payload je obavezan.");
        }

        ValidatePayload(envelope.Payload);
    }

    /// <summary>
    /// Validira payload deo canonical eventa.
    /// </summary>
    private static void ValidatePayload(WeatherSnapshotPayload payload)
    {
        if (payload.LocationId == Guid.Empty)
        {
            throw new InvalidOperationException("Payload location_id je obavezan.");
        }

        if (string.IsNullOrWhiteSpace(payload.City))
        {
            throw new InvalidOperationException("Payload city je obavezan.");
        }

        if (string.IsNullOrWhiteSpace(payload.Country))
        {
            throw new InvalidOperationException("Payload country je obavezan.");
        }

        if (string.IsNullOrWhiteSpace(payload.Source))
        {
            throw new InvalidOperationException("Payload source je obavezan.");
        }
    }
}
