package weatherapi

import (
	"context"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"strings"
	"time"

	"ingestion-weatherapi/internal/config"
)

/*
Client je nizak HTTP klijent za komunikaciju sa WeatherAPI source-om.

Ovaj klijent ne zna ništa o domain eventima, outbox-u ili application use case-u.
Njegov posao je samo HTTP komunikacija.
*/
type Client struct {
	httpClient *http.Client
	config     *config.Config
}

/*
NewClient pravi novu instancu WeatherAPI klijenta.
*/
func NewClient(cfg *config.Config) *Client {
	return &Client{
		httpClient: &http.Client{
			Timeout: 15 * time.Second,
		},
		config: cfg,
	}
}

/*
FetchCurrentRaw dohvaća raw JSON odgovor sa WeatherAPI current endpoint-a.

Query može biti naziv grada, npr. "Belgrade", ili kasnije koordinata.
*/
func (c *Client) FetchCurrentRaw(ctx context.Context, query string) ([]byte, error) {
	baseURL := strings.TrimRight(c.config.WeatherAPI.BaseURL, "/")
	endpoint := fmt.Sprintf("%s/current.json", baseURL)

	params := url.Values{}
	params.Set("key", c.config.WeatherAPI.APIKey)
	params.Set("q", query)
	params.Set("aqi", "no")

	finalURL := fmt.Sprintf("%s?%s", endpoint, params.Encode())

	request, err := http.NewRequestWithContext(ctx, http.MethodGet, finalURL, nil)
	if err != nil {
		return nil, fmt.Errorf("neuspešno kreiranje HTTP zahteva: %w", err)
	}

	response, err := c.httpClient.Do(request)
	if err != nil {
		return nil, fmt.Errorf("neuspešan HTTP poziv ka WeatherAPI: %w", err)
	}
	defer response.Body.Close()

	body, err := io.ReadAll(response.Body)
	if err != nil {
		return nil, fmt.Errorf("neuspešno čitanje response body-ja: %w", err)
	}

	if response.StatusCode < 200 || response.StatusCode >= 300 {
		return nil, fmt.Errorf(
			"weatherapi vratio neuspešan status %d: %s",
			response.StatusCode,
			string(body),
		)
	}

	return body, nil
}
