import { Global, Module } from '@nestjs/common';

import { appConfigProvider } from './config.provider';

// Globalni config modul da infrastructure adapteri ne ucitavaju environment direktno.
@Global()
@Module({
  providers: [appConfigProvider],
  exports: [appConfigProvider],
})
export class ConfigModule {}
