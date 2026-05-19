import { Controller, Get } from '@nestjs/common';

import {
  HealthCheckResult,
  HealthCheckService,
} from '../../../application/health/health-check.service';

// HTTP controller koji izlozi osnovni health endpoint za Docker i lokalne provere.
@Controller('health')
export class HealthController {
  constructor(private readonly healthCheckService: HealthCheckService) {}

  // Vraca status aplikacije na GET /health.
  @Get()
  check(): HealthCheckResult {
    return this.healthCheckService.check();
  }
}
