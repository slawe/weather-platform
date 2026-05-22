namespace Infrastructure.Config;

/// <summary>
/// Cita database konfiguraciju iz environment promenljivih.
/// </summary>
public sealed class DatabaseConfig
{
    public string Host { get; }

    public int Port { get; }

    public string Database { get; }

    public string User { get; }

    public string Password { get; }

    private DatabaseConfig(
        string host,
        int port,
        string database,
        string user,
        string password
    )
    {
        Host = host;
        Port = port;
        Database = database;
        User = user;
        Password = password;
    }

    /// <summary>
    /// Kreira config objekat iz environment promenljivih.
    /// </summary>
    public static DatabaseConfig FromEnvironment()
    {
        return new DatabaseConfig(
            GetString("DB_HOST", "processing-core-db"),
            GetInt("DB_PORT", 5432),
            GetString("DB_NAME", "processing_core_db"),
            GetString("DB_USER", "demo"),
            GetString("DB_PASSWORD", "demo")
        );
    }

    /// <summary>
    /// Pravi PostgreSQL connection string za Npgsql.
    /// </summary>
    public string ToConnectionString()
    {
        return $"Host={Host};Port={Port};Database={Database};Username={User};Password={Password};Include Error Detail=true";
    }

    /// <summary>
    /// Cita string environment vrednost ili vraca default.
    /// </summary>
    private static string GetString(string name, string defaultValue)
    {
        var value = Environment.GetEnvironmentVariable(name);

        return string.IsNullOrWhiteSpace(value) ? defaultValue : value;
    }

    /// <summary>
    /// Cita int environment vrednost ili vraca default.
    /// </summary>
    private static int GetInt(string name, int defaultValue)
    {
        var value = Environment.GetEnvironmentVariable(name);

        return int.TryParse(value, out var parsedValue) ? parsedValue : defaultValue;
    }
}
