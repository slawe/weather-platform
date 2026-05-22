import { WebSocketGateway, WebSocketServer } from '@nestjs/websockets';
import { Server } from 'socket.io';

import { CurrentWeatherComparison } from '../../domain/comparison/weather-comparison';

// Socket.IO gateway koji salje azurirani comparison povezanim klijentima.
@WebSocketGateway({
  cors: {
    origin: '*',
  },
})
export class ComparisonGateway {
  @WebSocketServer()
  private readonly server!: Server;

  // Emithuje novi comparison kroz dogovoreni realtime event.
  publishComparisonUpdated(comparison: CurrentWeatherComparison): void {
    this.server.emit('weather.comparison.updated', comparison);
  }
}
