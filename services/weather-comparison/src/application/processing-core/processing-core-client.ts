import { WeatherSnapshot } from '../../domain/weather/weather-snapshot';

export type WeatherAverage = {
  locationId: string;
  source: string;
  periodStart: string;
  averageTemperatureC: number;
};

export type WeatherHistoryQuery = {
  locationId?: string;
  source?: string;
  from?: string;
  to?: string;
};

// Definise zavisnost ka processing-core API-ju bez vezivanja application sloja za HTTP detalje.
export abstract class ProcessingCoreClient {
  abstract getCurrentSnapshots(): Promise<WeatherSnapshot[]>;
  abstract getHistory(query: WeatherHistoryQuery): Promise<WeatherSnapshot[]>;
  abstract getWeeklyAverages(query: WeatherHistoryQuery): Promise<WeatherAverage[]>;
  abstract getMonthlyAverages(query: WeatherHistoryQuery): Promise<WeatherAverage[]>;
  abstract getQuarterlyAverages(query: WeatherHistoryQuery): Promise<WeatherAverage[]>;
}
