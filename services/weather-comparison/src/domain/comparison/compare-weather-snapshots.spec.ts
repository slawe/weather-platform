import * as assert from 'node:assert/strict';
import { test } from 'node:test';

import { WeatherSnapshot } from '../weather/weather-snapshot';
import { compareWeatherSnapshots } from './compare-weather-snapshots';

test('compareWeatherSnapshots groups snapshots by location id', () => {
  const comparison = compareWeatherSnapshots([
    snapshot({ locationId: 'location-a', source: 'weatherapi' }),
    snapshot({ locationId: 'location-a', source: 'open-meteo' }),
    snapshot({ locationId: 'location-b', source: 'weatherapi' }),
  ]);

  assert.equal(comparison.locations.length, 2);
  assert.equal(comparison.locations[0].locationId, 'location-a');
  assert.equal(comparison.locations[0].sources.length, 2);
  assert.equal(comparison.locations[1].locationId, 'location-b');
  assert.equal(comparison.locations[1].sources.length, 1);
});

test('compareWeatherSnapshots calculates differences between two sources', () => {
  const comparison = compareWeatherSnapshots([
    snapshot({
      source: 'weatherapi',
      temperatureC: 20.1,
      windSpeedKmh: 10.2,
      weatherCode: 1003,
    }),
    snapshot({
      source: 'open-meteo',
      temperatureC: 25.6,
      windSpeedKmh: 13.8,
      weatherCode: 2,
    }),
  ]);

  assert.deepEqual(comparison.locations[0].difference, {
    sourceA: 'open-meteo',
    sourceB: 'weatherapi',
    temperatureDifferenceC: 5.5,
    windSpeedDifferenceKmh: 3.6,
    weatherCodeDifferent: true,
  });
});

test('compareWeatherSnapshots returns null difference when location has one source', () => {
  const comparison = compareWeatherSnapshots([
    snapshot({ source: 'weatherapi' }),
  ]);

  assert.equal(comparison.locations[0].difference, null);
});

test('compareWeatherSnapshots rounds numeric differences to two decimals', () => {
  const comparison = compareWeatherSnapshots([
    snapshot({
      source: 'weatherapi',
      temperatureC: 20.111,
      windSpeedKmh: 7.444,
    }),
    snapshot({
      source: 'open-meteo',
      temperatureC: 21.445,
      windSpeedKmh: 9.999,
    }),
  ]);

  assert.equal(comparison.locations[0].difference?.temperatureDifferenceC, 1.33);
  assert.equal(comparison.locations[0].difference?.windSpeedDifferenceKmh, 2.56);
});

// Kreira domain snapshot za comparison testove uz mogucnost override-a bitnih polja.
function snapshot(overrides: Partial<WeatherSnapshot> = {}): WeatherSnapshot {
  return {
    eventId: 'event-id',
    locationId: 'location-a',
    city: 'Belgrade',
    country: 'Serbia',
    latitude: 44.8178,
    longitude: 20.4569,
    temperatureC: 20,
    windSpeedKmh: 5,
    weatherCode: 2,
    observedAt: '2026-05-21T15:00:00Z',
    source: 'open-meteo',
    producer: 'test-producer',
    receivedAt: '2026-05-21T15:00:01Z',
    ...overrides,
  };
}
