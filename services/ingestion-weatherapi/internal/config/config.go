package config

import (
	"fmt"
	"os"
	"strconv"
)

/*
Config predstavlja centralnu konfiguraciju servisa.

Konfiguraciju za sada čitamo iz environment varijabli, jer je to prirodno
za Docker i mikroservisni stil rada.
*/
type Config struct {
	App        AppConfig
	Database   DatabaseConfig
	RabbitMQ   RabbitMQConfig
	WeatherAPI WeatherAPIConfig
	Scheduler  SchedulerConfig
}

/*
AppConfig sadrži osnovna podešavanja same aplikacije.
*/
type AppConfig struct {
	Env         string
	ServiceName string
	SourceName  string
}

/*
DatabaseConfig sadrži konekcione podatke za Postgres bazu.
*/
type DatabaseConfig struct {
	Host     string
	Port     int
	Name     string
	User     string
	Password string
}

/*
RabbitMQConfig sadrži konekcione podatke i messaging topologiju.
*/
type RabbitMQConfig struct {
	Host         string
	Port         int
	User         string
	Password     string
	VHost        string
	Exchange     string
	ExchangeType string
	RoutingKey   string
}

/*
DefaultLocationConfig predstavlja jednu podrazumevanu lokaciju iz konfiguracije.
*/
type DefaultLocationConfig struct {
	ID        string
	Name      string
	Country   string
	Latitude  float64
	Longitude float64
}

/*
WeatherAPIConfig sadrži podešavanja za WeatherAPI provider.
*/
type WeatherAPIConfig struct {
	BaseURL          string
	APIKey           string
	DefaultQuery     string
	DefaultLocations []DefaultLocationConfig
}

/*
SchedulerConfig sadrži intervale za periodični ingestion i outbox publish.
*/
type SchedulerConfig struct {
	FetchIntervalSeconds         int
	OutboxPublishIntervalSeconds int
}

/*
Load učitava konfiguraciju iz environment varijabli.

Ovde odmah radimo osnovnu validaciju da servis pukne rano i jasno
ako nedostaje nešto obavezno.
*/
func Load() (*Config, error) {
	dbPort, err := getEnvAsInt("DB_PORT", 5432)
	if err != nil {
		return nil, fmt.Errorf("DB_PORT nije validan broj: %w", err)
	}

	rabbitPort, err := getEnvAsInt("RABBITMQ_PORT", 5672)
	if err != nil {
		return nil, fmt.Errorf("RABBITMQ_PORT nije validan broj: %w", err)
	}

	dbHost, err := getRequiredEnv("DB_HOST")
	if err != nil {
		return nil, err
	}

	dbName, err := getRequiredEnv("DB_NAME")
	if err != nil {
		return nil, err
	}

	dbUser, err := getRequiredEnv("DB_USER")
	if err != nil {
		return nil, err
	}

	dbPassword, err := getRequiredEnv("DB_PASSWORD")
	if err != nil {
		return nil, err
	}

	rabbitHost, err := getRequiredEnv("RABBITMQ_HOST")
	if err != nil {
		return nil, err
	}

	rabbitUser, err := getRequiredEnv("RABBITMQ_USER")
	if err != nil {
		return nil, err
	}

	rabbitPassword, err := getRequiredEnv("RABBITMQ_PASSWORD")
	if err != nil {
		return nil, err
	}

	rabbitExchange, err := getRequiredEnv("RABBITMQ_EXCHANGE")
	if err != nil {
		return nil, err
	}

	rabbitRoutingKey, err := getRequiredEnv("RABBITMQ_ROUTING_KEY")
	if err != nil {
		return nil, err
	}

	weatherBaseURL, err := getRequiredEnv("WEATHERAPI_BASE_URL")
	if err != nil {
		return nil, err
	}

	weatherAPIKey, err := getRequiredEnv("WEATHERAPI_KEY")
	if err != nil {
		return nil, err
	}

	cfg := &Config{
		App: AppConfig{
			Env:         getEnv("APP_ENV", "local"),
			ServiceName: getEnv("SERVICE_NAME", "ingestion-weatherapi"),
			SourceName:  getEnv("SOURCE_NAME", "weatherapi"),
		},
		Database: DatabaseConfig{
			Host:     dbHost,
			Port:     dbPort,
			Name:     dbName,
			User:     dbUser,
			Password: dbPassword,
		},
		RabbitMQ: RabbitMQConfig{
			Host:         rabbitHost,
			Port:         rabbitPort,
			User:         rabbitUser,
			Password:     rabbitPassword,
			VHost:        getEnv("RABBITMQ_VHOST", "/"),
			Exchange:     rabbitExchange,
			ExchangeType: getEnv("RABBITMQ_EXCHANGE_TYPE", "topic"),
			RoutingKey:   rabbitRoutingKey,
		},
		WeatherAPI: WeatherAPIConfig{
			BaseURL:      weatherBaseURL,
			APIKey:       weatherAPIKey,
			DefaultQuery: getEnv("WEATHERAPI_QUERY_DEFAULT", "Belgrade"),
			DefaultLocations: []DefaultLocationConfig{
				{
					ID:        "11111111-1111-1111-1111-111111111111",
					Name:      "Belgrade",
					Country:   "Serbia",
					Latitude:  44.8178,
					Longitude: 20.4569,
				},
				{
					ID:        "22222222-2222-2222-2222-222222222222",
					Name:      "Novi Sad",
					Country:   "Serbia",
					Latitude:  45.2671,
					Longitude: 19.8335,
				},
				{
					ID:        "33333333-3333-3333-3333-333333333333",
					Name:      "Niš",
					Country:   "Serbia",
					Latitude:  43.3209,
					Longitude: 21.8958,
				},
			},
		},
		Scheduler: SchedulerConfig{
			FetchIntervalSeconds:         getEnvAsPositiveInt("WEATHER_FETCH_INTERVAL_SECONDS", 60),
			OutboxPublishIntervalSeconds: getEnvAsPositiveInt("OUTBOX_PUBLISH_INTERVAL_SECONDS", 60),
		},
	}

	return cfg, nil
}

/*
getEnv vraća vrednost environment varijable ili default vrednost ako nije postavljena.
*/
func getEnv(key, fallback string) string {
	value := os.Getenv(key)
	if value == "" {
		return fallback
	}

	return value
}

/*
getRequiredEnv vraća vrednost obavezne environment varijable.
*/
func getRequiredEnv(key string) (string, error) {
	value := os.Getenv(key)
	if value == "" {
		return "", fmt.Errorf("nedostaje obavezna environment varijabla: %s", key)
	}

	return value, nil
}

/*
getEnvAsInt vraća int vrednost environment varijable ili default vrednost.
*/
func getEnvAsInt(key string, fallback int) (int, error) {
	value := os.Getenv(key)
	if value == "" {
		return fallback, nil
	}

	parsed, err := strconv.Atoi(value)
	if err != nil {
		return 0, err
	}

	return parsed, nil
}

/*
getEnvAsPositiveInt vraća pozitivnu int vrednost ili fallback.
*/
func getEnvAsPositiveInt(key string, fallback int) int {
	value := os.Getenv(key)
	if value == "" {
		return fallback
	}

	parsed, err := strconv.Atoi(value)
	if err != nil || parsed <= 0 {
		return fallback
	}

	return parsed
}
