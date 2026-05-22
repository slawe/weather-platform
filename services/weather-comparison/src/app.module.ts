import { Module } from '@nestjs/common';

import { ConfigModule } from './config/config.module';
import { ComparisonModule } from './infrastructure/comparison/comparison.module';
import { HealthController } from './infrastructure/http/health/health.controller';

// Root NestJS modul za pocetno povezivanje buducih application i infrastructure modula.
@Module({
  imports: [ConfigModule, ComparisonModule],
  controllers: [HealthController],
})
export class AppModule {}
