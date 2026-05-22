import { Injectable } from '@nestjs/common';

import {
  ProcessingCoreClient,
  WeatherAverage,
  WeatherHistoryQuery,
} from '../processing-core/processing-core-client';
import { WeatherSnapshot } from '../../domain/weather/weather-snapshot';

export type WeatherHistoryResponse = {
  history: WeatherSnapshot[];
  weekly: WeatherAverage[];
  monthly: WeatherAverage[];
  quarterly: WeatherAverage[];
};

// Application servis koji sastavlja history podatke potrebne dashboard-u.
@Injectable()
export class WeatherHistoryService {
  constructor(private readonly processingCoreClient: ProcessingCoreClient) {}

  // Ucitava raw history i agregate kroz processing-core REST API.
  async getHistory(query: WeatherHistoryQuery): Promise<WeatherHistoryResponse> {
    const [history, weekly, monthly, quarterly] = await Promise.all([
      this.processingCoreClient.getHistory(query),
      this.processingCoreClient.getWeeklyAverages(query),
      this.processingCoreClient.getMonthlyAverages(query),
      this.processingCoreClient.getQuarterlyAverages(query),
    ]);

    return {
      history,
      weekly,
      monthly,
      quarterly,
    };
  }
}
