import { NestFactory } from '@nestjs/core';

import { AppModule } from './app.module';
import { loadConfig } from './config/config';

// Pokrece NestJS aplikaciju i veze HTTP/Socket.IO sloj za port iz konfiguracije.
async function bootstrap(): Promise<void> {
  const config = loadConfig();
  const app = await NestFactory.create(AppModule);

  await app.listen(config.port);
}

void bootstrap();
