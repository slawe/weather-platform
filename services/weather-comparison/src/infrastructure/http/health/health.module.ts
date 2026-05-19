import { Module } from '@nestjs/common';

import { HealthCheckService } from '../../../application/health/health-check.service';
import { loadConfig } from '../../../config/config';
import { HealthController } from './health.controller';

// NestJS modul koji povezuje health application servis sa HTTP controllerom.
@Module({
  controllers: [HealthController],
  providers: [
    {
      provide: HealthCheckService,
      useFactory: (): HealthCheckService => {
        const config = loadConfig();

        return new HealthCheckService(config.serviceName);
      },
    },
  ],
})
export class HealthModule {}
