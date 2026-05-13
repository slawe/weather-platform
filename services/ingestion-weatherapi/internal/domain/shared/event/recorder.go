package event

/*
Recorder služi da agregat može da beleži domain evente tokom svog rada.

Na ovaj način domen ostaje mesto gde događaji nastaju,
a application sloj ih samo preuzima i prosleđuje dalje.
*/
type Recorder struct {
	events []DomainEvent
}

/*
Record dodaje novi domain event u internu listu.
*/
func (r *Recorder) Record(event DomainEvent) {
	r.events = append(r.events, event)
}

/*
Release vraća sve zabeležene evente i prazni internu listu.

Ovo je bitno da isti eventi ne ostanu zalepljeni za agregat
i ne budu prosleđeni više puta.
*/
func (r *Recorder) Release() []DomainEvent {
	released := r.events
	r.events = nil

	return released
}
