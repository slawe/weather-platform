package weather

/*
Location predstavlja domen entitet lokacije za koju prikupljamo vremenske podatke.
*/
type Location struct {
	ID        string
	Name      string
	Country   string
	Latitude  float64
	Longitude float64
	IsActive  bool
}

/*
NewLocation pravi novu instancu Location entiteta.
*/
func NewLocation(
	id string,
	name string,
	country string,
	latitude float64,
	longitude float64,
	isActive bool,
) Location {
	return Location{
		ID:        id,
		Name:      name,
		Country:   country,
		Latitude:  latitude,
		Longitude: longitude,
		IsActive:  isActive,
	}
}
