import { WeatherSnapshot } from '../weather/weather-snapshot';
import {
  CurrentWeatherComparison,
  LocationWeatherComparison,
  WeatherSnapshotDifference,
} from './weather-comparison';

// Racuna comparison model iz current snapshot-a bez zavisnosti od NestJS-a ili HTTP sloja.
export function compareWeatherSnapshots(
  snapshots: WeatherSnapshot[],
): CurrentWeatherComparison {
  const groupedByLocation = groupSnapshotsByLocation(snapshots);
  const locations = [...groupedByLocation.values()].map(compareLocationSnapshots);

  return {
    generatedAt: new Date().toISOString(),
    locations,
  };
}

// Grupise snapshot-e po lokaciji da bi se poredili razliciti izvori za isti grad.
function groupSnapshotsByLocation(
  snapshots: WeatherSnapshot[],
): Map<string, WeatherSnapshot[]> {
  const grouped = new Map<string, WeatherSnapshot[]>();

  for (const snapshot of snapshots) {
    const existingSnapshots = grouped.get(snapshot.locationId) ?? [];
    existingSnapshots.push(snapshot);
    grouped.set(snapshot.locationId, existingSnapshots);
  }

  return grouped;
}

// Pravi comparison za jednu lokaciju i bira stabilan redosled source-ova.
function compareLocationSnapshots(
  snapshots: WeatherSnapshot[],
): LocationWeatherComparison {
  const sortedSnapshots = [...snapshots].sort((a, b) =>
    a.source.localeCompare(b.source),
  );
  const primarySnapshot = sortedSnapshots[0];

  return {
    locationId: primarySnapshot.locationId,
    city: primarySnapshot.city,
    country: primarySnapshot.country,
    latitude: primarySnapshot.latitude,
    longitude: primarySnapshot.longitude,
    sources: sortedSnapshots.map((snapshot) => ({
      source: snapshot.source,
      temperatureC: snapshot.temperatureC,
      windSpeedKmh: snapshot.windSpeedKmh,
      weatherCode: snapshot.weatherCode,
      observedAt: snapshot.observedAt,
    })),
    difference: createDifference(sortedSnapshots),
  };
}

// Racuna razliku izmedju prva dva source-a kada postoje bar dva izvora za lokaciju.
function createDifference(
  snapshots: WeatherSnapshot[],
): WeatherSnapshotDifference | null {
  if (snapshots.length < 2) {
    return null;
  }

  const [sourceA, sourceB] = snapshots;

  return {
    sourceA: sourceA.source,
    sourceB: sourceB.source,
    temperatureDifferenceC: roundToTwoDecimals(
      Math.abs(sourceA.temperatureC - sourceB.temperatureC),
    ),
    windSpeedDifferenceKmh: roundToTwoDecimals(
      Math.abs(sourceA.windSpeedKmh - sourceB.windSpeedKmh),
    ),
    weatherCodeDifferent: sourceA.weatherCode !== sourceB.weatherCode,
  };
}

// Zaokruzuje decimalne razlike da API response ostane citljiv.
function roundToTwoDecimals(value: number): number {
  return Math.round(value * 100) / 100;
}
