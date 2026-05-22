export type WeatherSnapshot = {
  eventId: string;
  locationId: string;
  city: string;
  country: string;
  latitude: number;
  longitude: number;
  temperatureC: number;
  windSpeedKmh: number;
  weatherCode: number;
  observedAt: string;
  source: string;
  producer: string;
  receivedAt: string;
};
