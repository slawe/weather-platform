import { Controller, Get, Inject } from '@nestjs/common';

import { AppConfig } from '../../../config/config';
import { APP_CONFIG } from '../../../config/config.provider';

type HealthResponse = {
  status: 'ok';
  service: string;
  timestamp: string;
};

// HTTP controller koji izlozi osnovni health endpoint za Docker i lokalne provere.
@Controller('health')
export class HealthController {
  constructor(@Inject(APP_CONFIG) private readonly config: AppConfig) {}

  // Vraca status aplikacije na GET /health.
  @Get()
  check(): HealthResponse {
    return {
      status: 'ok',
      service: this.config.serviceName,
      timestamp: new Date().toISOString(),
    };
  }
}
