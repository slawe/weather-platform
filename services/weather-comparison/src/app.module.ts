import { Module } from '@nestjs/common';

import { HealthModule } from './infrastructure/http/health/health.module';

// Root NestJS modul za pocetno povezivanje buducih application i infrastructure modula.
@Module({
  imports: [HealthModule],
})
export class AppModule {}
