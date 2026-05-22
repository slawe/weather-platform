import { Module } from '@nestjs/common';

import { CurrentComparisonService } from '../../application/comparison/current-comparison.service';
import { WeatherHistoryService } from '../../application/comparison/weather-history.service';
import { ProcessingCoreClient } from '../../application/processing-core/processing-core-client';
import { ComparisonController } from '../http/comparison/comparison.controller';
import { HistoryController } from '../http/comparison/history.controller';
import { ProcessingCoreHttpClient } from '../http/processing-core/processing-core-http-client';
import { ComparisonGateway } from '../realtime/comparison.gateway';
import { ComparisonPollerService } from '../scheduler/comparison-poller.service';

// NestJS modul koji povezuje comparison use case, HTTP adapter i Socket.IO gateway.
@Module({
  controllers: [ComparisonController, HistoryController],
  providers: [
    CurrentComparisonService,
    WeatherHistoryService,
    ComparisonGateway,
    ComparisonPollerService,
    {
      provide: ProcessingCoreClient,
      useClass: ProcessingCoreHttpClient,
    },
  ],
})
export class ComparisonModule {}
