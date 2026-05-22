using Microsoft.AspNetCore.Mvc;

namespace Api.Controllers;

/// <summary>
/// HTTP endpoint za osnovnu proveru da li processing-core servis radi.
/// </summary>
[ApiController]
[Route("api/health")]
public sealed class HealthController : ControllerBase
{
    /// <summary>
    /// Vraca osnovne informacije o stanju servisa.
    /// </summary>
    [HttpGet]
    public IActionResult Get()
    {
        return Ok(new
        {
            status = "ok",
            service = Environment.GetEnvironmentVariable("SERVICE_NAME") ?? "processing-core",
            technology = ".NET",
        });
    }
}
