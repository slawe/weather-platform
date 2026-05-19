export type AppConfig = {
  appEnv: string;
  port: number;
  serviceName: string;
  database: DatabaseConfig;
  rabbitmq: RabbitMqConfig;
};

export type DatabaseConfig = {
  host: string;
  port: number;
  database: string;
  user: string;
  password: string;
};

export type RabbitMqConfig = {
  host: string;
  port: number;
  user: string;
  password: string;
  vhost: string;
  exchange: string;
  exchangeType: string;
  consumeRoutingKey: string;
};

// Ucitava konfiguraciju iz environment promenljivih i drzi je na jednom mestu.
export function loadConfig(): AppConfig {
  return {
    appEnv: process.env.APP_ENV ?? 'local',
    port: numberFromEnv('PORT', 3000),
    serviceName: process.env.SERVICE_NAME ?? 'weather-comparison',
    database: {
      host: process.env.DB_HOST ?? 'weather-comparison-db',
      port: numberFromEnv('DB_PORT', 5432),
      database: process.env.DB_NAME ?? 'weather_comparison_db',
      user: process.env.DB_USER ?? 'demo',
      password: process.env.DB_PASSWORD ?? 'demo',
    },
    rabbitmq: {
      host: process.env.RABBITMQ_HOST ?? 'rabbitmq',
      port: numberFromEnv('RABBITMQ_PORT', 5672),
      user: process.env.RABBITMQ_USER ?? 'demo',
      password: process.env.RABBITMQ_PASSWORD ?? 'demo',
      vhost: process.env.RABBITMQ_VHOST ?? '/',
      exchange: process.env.RABBITMQ_EXCHANGE ?? 'weather.events',
      exchangeType: process.env.RABBITMQ_EXCHANGE_TYPE ?? 'topic',
      consumeRoutingKey: process.env.RABBITMQ_CONSUME_ROUTING_KEY ?? 'weather.snapshot.updated',
    },
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
