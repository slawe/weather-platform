import {
  Inject,
  Injectable,
  Logger,
  OnModuleDestroy,
  OnModuleInit,
} from '@nestjs/common';

import { CurrentComparisonService } from '../../application/comparison/current-comparison.service';
import { AppConfig } from '../../config/config';
import { APP_CONFIG } from '../../config/config.provider';
import { ComparisonGateway } from '../realtime/comparison.gateway';

// Periodicno osvezava comparison iz processing-core API-ja i salje Socket.IO update.
@Injectable()
export class ComparisonPollerService implements OnModuleInit, OnModuleDestroy {
  private readonly logger = new Logger(ComparisonPollerService.name);
  private intervalRef: NodeJS.Timeout | null = null;

  constructor(
    private readonly currentComparisonService: CurrentComparisonService,
    private readonly comparisonGateway: ComparisonGateway,
    @Inject(APP_CONFIG) private readonly config: AppConfig,
  ) {}

  // Pokrece polling kada NestJS modul bude inicijalizovan.
  onModuleInit(): void {
    void this.refreshAndPublish();
    this.intervalRef = setInterval(
      () => void this.refreshAndPublish(),
      this.config.processingCorePollIntervalMs,
    );
  }

  // Zaustavlja interval kada se aplikacija gasi.
  onModuleDestroy(): void {
    if (this.intervalRef !== null) {
      clearInterval(this.intervalRef);
    }
  }

  // Osvezava cache i emituje update, a gresku samo loguje da polling nastavi da radi.
  private async refreshAndPublish(): Promise<void> {
    try {
      const comparison =
        await this.currentComparisonService.refreshCurrentComparison();

      this.comparisonGateway.publishComparisonUpdated(comparison);
    } catch (error) {
      this.logger.warn(
        error instanceof Error
          ? error.message
          : 'Unknown comparison polling error.',
      );
    }
  }
}
