export type AppConfig = {
  appEnv: string;
  port: number;
  serviceName: string;
  processingCoreApiBaseUrl: string;
  processingCorePollIntervalMs: number;
};

// Ucitava konfiguraciju iz environment promenljivih i drzi je na jednom mestu.
export function loadConfig(): AppConfig {
  return {
    appEnv: process.env.APP_ENV ?? 'local',
    port: numberFromEnv('PORT', 3000),
    serviceName: process.env.SERVICE_NAME ?? 'weather-comparison',
    processingCoreApiBaseUrl:
      process.env.PROCESSING_CORE_API_BASE_URL ?? 'http://processing-core:8080',
    processingCorePollIntervalMs: numberFromEnv(
      'PROCESSING_CORE_POLL_INTERVAL_MS',
      5000,
    ),
  };
}

// Pretvara environment vrednost u broj i vraca default ako vrednost nije validna.
function numberFromEnv(name: string, defaultValue: number): number {
  const rawValue = process.env[name];

  if (rawValue === undefined || rawValue === '') {
    return defaultValue;
  }

  const parsedValue = Number(rawValue);

  if (Number.isNaN(parsedValue)) {
    return defaultValue;
  }

  return parsedValue;
}
