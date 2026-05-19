export type HealthCheckResult = {
  status: 'ok';
  service: string;
  timestamp: string;
};

// Vraca osnovno health stanje servisa bez zavisnosti od HTTP framework-a.
export class HealthCheckService {
  constructor(private readonly serviceName: string) {}

  // Kreira trenutni health response koji controller moze da vrati klijentu.
  check(): HealthCheckResult {
    return {
      status: 'ok',
      service: this.serviceName,
      timestamp: new Date().toISOString(),
    };
  }
}
