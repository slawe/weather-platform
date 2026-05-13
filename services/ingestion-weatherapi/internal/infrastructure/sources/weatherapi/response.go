package weatherapi

/*
CurrentWeatherResponse predstavlja minimalni deo WeatherAPI current.json odgovora
koji nam je potreban za mapiranje u naš canonical DTO.

Ne uvlačimo sva polja iz response-a, već samo ona koja zaista koristimo.
*/
type CurrentWeatherResponse struct {
	Location CurrentWeatherLocation `json:"location"`
	Current  CurrentWeatherCurrent  `json:"current"`
}

/*
CurrentWeatherLocation predstavlja location deo WeatherAPI odgovora.
*/
type CurrentWeatherLocation struct {
	Name      string  `json:"name"`
	Country   string  `json:"country"`
	Latitude  float64 `json:"lat"`
	Longitude float64 `json:"lon"`
}

/*
CurrentWeatherCurrent predstavlja current deo WeatherAPI odgovora.
*/
type CurrentWeatherCurrent struct {
	LastUpdated string                  `json:"last_updated"`
	TempC       float64                 `json:"temp_c"`
	WindKph     float64                 `json:"wind_kph"`
	Condition   CurrentWeatherCondition `json:"condition"`
}

/*
CurrentWeatherCondition predstavlja nested condition objekat.
*/
type CurrentWeatherCondition struct {
	Code int `json:"code"`
}
