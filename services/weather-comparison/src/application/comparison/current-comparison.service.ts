import { Injectable } from '@nestjs/common';

import { compareWeatherSnapshots } from '../../domain/comparison/compare-weather-snapshots';
import { CurrentWeatherComparison } from '../../domain/comparison/weather-comparison';
import { ProcessingCoreClient } from '../processing-core/processing-core-client';

// Application servis koji cuva poslednji izracunati comparison u memoriji.
@Injectable()
export class CurrentComparisonService {
  private currentComparison: CurrentWeatherComparison | null = null;

  constructor(private readonly processingCoreClient: ProcessingCoreClient) {}

  // Vraca poslednji comparison ili ga ucitava ako servis jos nema cache.
  async getCurrentComparison(): Promise<CurrentWeatherComparison> {
    if (this.currentComparison === null) {
      return this.refreshCurrentComparison();
    }

    return this.currentComparison;
  }

  // Ucitava current snapshot-e iz processing-core i racuna novi comparison.
  async refreshCurrentComparison(): Promise<CurrentWeatherComparison> {
    const snapshots = await this.processingCoreClient.getCurrentSnapshots();
    this.currentComparison = compareWeatherSnapshots(snapshots);

    return this.currentComparison;
  }
}
