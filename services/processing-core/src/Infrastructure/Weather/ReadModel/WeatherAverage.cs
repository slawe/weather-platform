namespace Infrastructure.Weather.ReadModel;

/// <summary>
/// Read model za prosečnu temperaturu u vremenskom periodu.
/// </summary>
public sealed record WeatherAverage(
    Guid LocationId,
    string Source,
    DateTimeOffset PeriodStart,
    decimal AverageTemperatureC
);
