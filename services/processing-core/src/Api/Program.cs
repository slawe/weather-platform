using Api.BackgroundServices;
using Application.Weather;
using Infrastructure.Config;
using Infrastructure.Messaging.RabbitMq;
using Infrastructure.Weather;
using Infrastructure.Weather.ReadModel;

var builder = WebApplication.CreateBuilder(args);

builder.Logging.AddFilter("Microsoft.AspNetCore.Hosting.Diagnostics", LogLevel.Warning);
builder.Logging.AddFilter("Microsoft.AspNetCore.Mvc", LogLevel.Warning);
builder.Logging.AddFilter("Microsoft.AspNetCore.Routing", LogLevel.Warning);

builder.Services.AddControllers();
builder.Services.AddSingleton(DatabaseConfig.FromEnvironment());
builder.Services.AddSingleton(RabbitMqConfig.FromEnvironment());
builder.Services.AddSingleton(WeatherHistoryRetentionConfig.FromEnvironment());
builder.Services.AddSingleton<RabbitMqConnectionFactory>();
builder.Services.AddSingleton<RabbitMqTopology>();
builder.Services.AddSingleton<ConsumedEventRepository>();
builder.Services.AddSingleton<WeatherSnapshotWriteRepository>();
builder.Services.AddSingleton<SignificantWeatherChangeDetector>();
builder.Services.AddSingleton<WeatherSnapshotFetchedProcessor>();
builder.Services.AddSingleton<WeatherHistoryRetentionCleanup>();
builder.Services.AddSingleton<RabbitMqWeatherConsumer>();
builder.Services.AddHostedService<RabbitMqConsumerHostedService>();
builder.Services.AddHostedService<WeatherHistoryRetentionHostedService>();
builder.Services.AddScoped<WeatherReadRepository>();

var app = builder.Build();

app.MapControllers();

app.Run();
