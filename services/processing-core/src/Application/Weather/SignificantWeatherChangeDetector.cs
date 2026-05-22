using Application.Messaging.DTO;

namespace Application.Weather;

/// <summary>
/// Odredjuje da li novi snapshot predstavlja znacajnu promenu za history tabelu.
/// </summary>
public sealed class SignificantWeatherChangeDetector
{
    /// <summary>
    /// Vraca true kada se promeni temperatura, vetar, weather code ili business dan.
    /// </summary>
    public bool HasSignificantChange(
        WeatherSnapshotPayload payload,
        LastWeatherHistorySnapshot? lastHistory
    )
    {
        if (lastHistory is null)
        {
            return true;
        }

        return payload.TemperatureC != lastHistory.TemperatureC
            || payload.WindSpeedKmh != lastHistory.WindSpeedKmh
            || payload.WeatherCode != lastHistory.WeatherCode
            || payload.ObservedAt.UtcDateTime.Date != lastHistory.ObservedAt.UtcDateTime.Date;
    }
}
