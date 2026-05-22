import { Controller, Get } from '@nestjs/common';

import { CurrentComparisonService } from '../../../application/comparison/current-comparison.service';
import { CurrentWeatherComparison } from '../../../domain/comparison/weather-comparison';

// HTTP controller koji vraca poslednji comparison za inicijalno ucitavanje frontenda.
@Controller('api/comparison')
export class ComparisonController {
  constructor(
    private readonly currentComparisonService: CurrentComparisonService,
  ) {}

  // Vraca trenutni comparison na GET /api/comparison/current.
  @Get('current')
  current(): Promise<CurrentWeatherComparison> {
    return this.currentComparisonService.getCurrentComparison();
  }
}
