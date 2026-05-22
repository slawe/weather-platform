using System.Globalization;
using System.Text.Json;
using System.Text.Json.Serialization;

namespace Application.Messaging.Serialization;

/// <summary>
/// Cita provider timestamp vrednosti koje mogu doci sa ili bez timezone offset-a.
/// </summary>
public sealed class FlexibleDateTimeOffsetJsonConverter : JsonConverter<DateTimeOffset>
{
    private static readonly string[] ProviderTimestampFormats =
    [
        "yyyy-MM-dd HH:mm",
        "yyyy-MM-dd HH:mm:ss",
        "yyyy-MM-dd'T'HH:mm",
        "yyyy-MM-dd'T'HH:mm:ss"
    ];

    /// <summary>
    /// Deserializuje ISO timestamp ili provider timestamp bez offset-a kao UTC vrednost.
    /// </summary>
    public override DateTimeOffset Read(
        ref Utf8JsonReader reader,
        Type typeToConvert,
        JsonSerializerOptions options
    )
    {
        var value = reader.GetString();

        if (string.IsNullOrWhiteSpace(value))
        {
            throw new JsonException("Timestamp vrednost je prazna.");
        }

        if (DateTimeOffset.TryParse(
            value,
            CultureInfo.InvariantCulture,
            DateTimeStyles.AssumeUniversal | DateTimeStyles.AdjustToUniversal,
            out var parsedWithOffset
        ))
        {
            return parsedWithOffset;
        }

        if (DateTime.TryParseExact(
            value,
            ProviderTimestampFormats,
            CultureInfo.InvariantCulture,
            DateTimeStyles.AssumeUniversal | DateTimeStyles.AdjustToUniversal,
            out var parsedWithoutOffset
        ))
        {
            return new DateTimeOffset(parsedWithoutOffset, TimeSpan.Zero);
        }

        throw new JsonException($"Timestamp vrednost nije podrzana: {value}");
    }

    /// <summary>
    /// Serijalizuje timestamp u stabilan ISO-8601 format.
    /// </summary>
    public override void Write(
        Utf8JsonWriter writer,
        DateTimeOffset value,
        JsonSerializerOptions options
    )
    {
        writer.WriteStringValue(value.ToUniversalTime().ToString("O", CultureInfo.InvariantCulture));
    }
}
