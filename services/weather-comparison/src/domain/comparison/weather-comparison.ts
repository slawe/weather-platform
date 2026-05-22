export type SourceSnapshotComparison = {
  source: string;
  temperatureC: number;
  windSpeedKmh: number;
  weatherCode: number;
  observedAt: string;
};

export type WeatherSnapshotDifference = {
  sourceA: string;
  sourceB: string;
  temperatureDifferenceC: number;
  windSpeedDifferenceKmh: number;
  weatherCodeDifferent: boolean;
};

export type LocationWeatherComparison = {
  locationId: string;
  city: string;
  country: string;
  latitude: number;
  longitude: number;
  sources: SourceSnapshotComparison[];
  difference: WeatherSnapshotDifference | null;
};

export type CurrentWeatherComparison = {
  generatedAt: string;
  locations: LocationWeatherComparison[];
};
