using Infrastructure.Weather.ReadModel;
using Microsoft.AspNetCore.Mvc;

namespace Api.Controllers.Weather;

/// <summary>
/// REST endpointi za current i history weather snapshot podatke.
/// </summary>
[ApiController]
[Route("api/weather/snapshots")]
public sealed class WeatherSnapshotsController : ControllerBase
{
    private readonly WeatherReadRepository repository;

    public WeatherSnapshotsController(WeatherReadRepository repository)
    {
        this.repository = repository;
    }

    /// <summary>
    /// Vraća trenutno aktuelne snapshot-e po location/source kombinaciji.
    /// </summary>
    [HttpGet("current")]
    public async Task<IActionResult> Current()
    {
        var snapshots = await repository.GetCurrentAsync();

        return Ok(snapshots);
    }

    /// <summary>
    /// Vraća kontrolisanu history listu uz opcione filtere.
    /// </summary>
    [HttpGet("history")]
    public async Task<IActionResult> History(
        [FromQuery] Guid? locationId,
        [FromQuery] string? source,
        [FromQuery] DateTimeOffset? from,
        [FromQuery] DateTimeOffset? to
    )
    {
        var snapshots = await repository.GetHistoryAsync(locationId, source, from, to);

        return Ok(snapshots);
    }
}
