import { AppConfig, loadConfig } from './config';

export const APP_CONFIG = Symbol('APP_CONFIG');

// Kreira jedan application config objekat koji NestJS moze da injektuje u adaptere.
export const appConfigProvider = {
  provide: APP_CONFIG,
  useFactory: (): AppConfig => loadConfig(),
};
