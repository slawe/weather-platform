using Infrastructure.Weather.ReadModel;
using Microsoft.AspNetCore.Mvc;

namespace Api.Controllers.Weather;

/// <summary>
/// REST endpointi za agregirane proseke temperature iz history tabele.
/// </summary>
[ApiController]
[Route("api/weather/averages")]
public sealed class WeatherAveragesController : ControllerBase
{
    private readonly WeatherReadRepository repository;

    public WeatherAveragesController(WeatherReadRepository repository)
    {
        this.repository = repository;
    }

    /// <summary>
    /// Vraća nedeljne proseke temperature.
    /// </summary>
    [HttpGet("weekly")]
    public async Task<IActionResult> Weekly(
        [FromQuery] Guid? locationId,
        [FromQuery] string? source,
        [FromQuery] DateTimeOffset? from,
        [FromQuery] DateTimeOffset? to
    )
    {
        var averages = await repository.GetWeeklyAveragesAsync(locationId, source, from, to);

        return Ok(averages);
    }

    /// <summary>
    /// Vraća mesečne proseke temperature.
    /// </summary>
    [HttpGet("monthly")]
    public async Task<IActionResult> Monthly(
        [FromQuery] Guid? locationId,
        [FromQuery] string? source,
        [FromQuery] DateTimeOffset? from,
        [FromQuery] DateTimeOffset? to
    )
    {
        var averages = await repository.GetMonthlyAveragesAsync(locationId, source, from, to);

        return Ok(averages);
    }

    /// <summary>
    /// Vraća tromesečne proseke temperature.
    /// </summary>
    [HttpGet("quarterly")]
    public async Task<IActionResult> Quarterly(
        [FromQuery] Guid? locationId,
        [FromQuery] string? source,
        [FromQuery] DateTimeOffset? from,
        [FromQuery] DateTimeOffset? to
    )
    {
        var averages = await repository.GetQuarterlyAveragesAsync(locationId, source, from, to);

        return Ok(averages);
    }
}
