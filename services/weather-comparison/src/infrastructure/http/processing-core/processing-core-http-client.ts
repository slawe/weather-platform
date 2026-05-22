import { Inject, Injectable } from '@nestjs/common';

import { ProcessingCoreClient } from '../../../application/processing-core/processing-core-client';
import {
  WeatherAverage,
  WeatherHistoryQuery,
} from '../../../application/processing-core/processing-core-client';
import { AppConfig } from '../../../config/config';
import { APP_CONFIG } from '../../../config/config.provider';
import { WeatherSnapshot } from '../../../domain/weather/weather-snapshot';

type ProcessingCoreSnapshotResponse = {
  eventId: string;
  locationId: string;
  city: string;
  country: string;
  latitude: number;
  longitude: number;
  temperatureC: string | number;
  windSpeedKmh: string | number;
  weatherCode: number;
  observedAt: string;
  source: string;
  producer: string;
  receivedAt: string;
};

type ProcessingCoreAverageResponse = {
  locationId: string;
  source: string;
  periodStart: string;
  averageTemperatureC: string | number;
};

// HTTP adapter koji cita current snapshot-e iz processing-core REST API-ja.
@Injectable()
export class ProcessingCoreHttpClient implements ProcessingCoreClient {
  constructor(@Inject(APP_CONFIG) private readonly config: AppConfig) {}

  // Poziva processing-core current endpoint i mapira response u domain snapshot model.
  async getCurrentSnapshots(): Promise<WeatherSnapshot[]> {
    const response = await fetch(
      `${this.config.processingCoreApiBaseUrl}/api/weather/snapshots/current`,
    );

    if (!response.ok) {
      throw new Error(
        `Processing-core current snapshots request failed with status ${response.status}.`,
      );
    }

    const snapshots =
      (await response.json()) as ProcessingCoreSnapshotResponse[];

    return snapshots.map(mapSnapshotResponse);
  }

  // Poziva processing-core history endpoint za izabranu lokaciju i opseg.
  async getHistory(query: WeatherHistoryQuery): Promise<WeatherSnapshot[]> {
    const snapshots = await this.getJson<ProcessingCoreSnapshotResponse[]>(
      '/api/weather/snapshots/history',
      query,
    );

    return snapshots.map(mapSnapshotResponse);
  }

  // Poziva processing-core weekly average endpoint.
  async getWeeklyAverages(query: WeatherHistoryQuery): Promise<WeatherAverage[]> {
    return this.getAverages('/api/weather/averages/weekly', query);
  }

  // Poziva processing-core monthly average endpoint.
  async getMonthlyAverages(query: WeatherHistoryQuery): Promise<WeatherAverage[]> {
    return this.getAverages('/api/weather/averages/monthly', query);
  }

  // Poziva processing-core quarterly average endpoint.
  async getQuarterlyAverages(
    query: WeatherHistoryQuery,
  ): Promise<WeatherAverage[]> {
    return this.getAverages('/api/weather/averages/quarterly', query);
  }

  // Ucitava average response i normalizuje decimalne vrednosti.
  private async getAverages(
    path: string,
    query: WeatherHistoryQuery,
  ): Promise<WeatherAverage[]> {
    const averages = await this.getJson<ProcessingCoreAverageResponse[]>(
      path,
      query,
    );

    return averages.map((average) => ({
      locationId: average.locationId,
      source: average.source,
      periodStart: average.periodStart,
      averageTemperatureC: Number(average.averageTemperatureC),
    }));
  }

  // Izvrsava GET zahtev prema processing-core API-ju.
  private async getJson<T>(path: string, query: WeatherHistoryQuery = {}): Promise<T> {
    const url = new URL(`${this.config.processingCoreApiBaseUrl}${path}`);

    for (const [key, value] of Object.entries(query)) {
      if (value !== undefined && value !== '') {
        url.searchParams.set(key, value);
      }
    }

    const response = await fetch(url);

    if (!response.ok) {
      throw new Error(
        `Processing-core request to ${path} failed with status ${response.status}.`,
      );
    }

    return (await response.json()) as T;
  }
}

// Pretvara API response u numericki stabilan domain model.
function mapSnapshotResponse(
  snapshot: ProcessingCoreSnapshotResponse,
): WeatherSnapshot {
  return {
    eventId: snapshot.eventId,
    locationId: snapshot.locationId,
    city: snapshot.city,
    country: snapshot.country,
    latitude: snapshot.latitude,
    longitude: snapshot.longitude,
    temperatureC: Number(snapshot.temperatureC),
    windSpeedKmh: Number(snapshot.windSpeedKmh),
    weatherCode: snapshot.weatherCode,
    observedAt: snapshot.observedAt,
    source: snapshot.source,
    producer: snapshot.producer,
    receivedAt: snapshot.receivedAt,
  };
}
