package main

import (
	"encoding/json"
	"log"
	"net/http"
	"os"
)

// HealthResponse predstavlja jednostavan JSON odgovor health endpoint-a.
type HealthResponse struct {
	Status  string `json:"status"`
	Service string `json:"service"`
}

// main je ulazna tačka aplikacije.
// U ovom koraku podižemo minimalan HTTP server kako bismo potvrdili:
// 1) da Go servis može da sluša port unutar kontejnera
// 2) da mu možemo pristupiti sa host mašine
// 3) da imamo osnovu za dalje endpoint-e kao što će biti /fetch
func main() {
	port := os.Getenv("PORT")
	if port == "" {
		port = "8080"
	}

	mux := http.NewServeMux()

	// Health endpoint koristimo za proveru da li je servis živ.
	mux.HandleFunc("/health", func(w http.ResponseWriter, r *http.Request) {
		response := HealthResponse{
			Status:  "ok",
			Service: "ingestion-weatherapi",
		}

		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusOK)

		err := json.NewEncoder(w).Encode(response)
		if err != nil {
			log.Printf("greška pri slanju health odgovora: %v", err)
		}
	})

	// Root endpoint je samo pomoćni informativni endpoint.
	mux.HandleFunc("/", func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)

		_, err := w.Write([]byte("ingestion-weatherapi radi"))
		if err != nil {
			log.Printf("greška pri slanju root odgovora: %v", err)
		}
	})

	address := ":" + port

	log.Printf("pokrećem HTTP server na %s", address)

	err := http.ListenAndServe(address, mux)
	if err != nil {
		log.Fatalf("server je stao: %v", err)
	}
}
