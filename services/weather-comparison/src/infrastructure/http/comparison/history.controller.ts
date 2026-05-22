import { Controller, Get, Query } from '@nestjs/common';

import {
  WeatherHistoryResponse,
  WeatherHistoryService,
} from '../../../application/comparison/weather-history.service';

type HistoryQuery = {
  locationId?: string;
  source?: string;
  from?: string;
  to?: string;
};

// HTTP controller koji frontendu isporucuje history i agregirane proseke po lokaciji.
@Controller('api/comparison/history')
export class HistoryController {
  constructor(private readonly weatherHistoryService: WeatherHistoryService) {}

  // Vraca raw history, weekly, monthly i quarterly proseke.
  @Get()
  history(@Query() query: HistoryQuery): Promise<WeatherHistoryResponse> {
    return this.weatherHistoryService.getHistory(query);
  }
}
