package rabbitmq

import (
	"context"
	"encoding/json"
	"fmt"

	amqp "github.com/rabbitmq/amqp091-go"

	outboxdto "ingestion-weatherapi/internal/application/outbox/dto"
	"ingestion-weatherapi/internal/config"
)

/*
Publisher objavljuje outbox poruke na RabbitMQ exchange.
*/
type Publisher struct {
	config *config.Config
}

/*
NewPublisher pravi novu RabbitMQ publisher instancu.
*/
func NewPublisher(cfg *config.Config) *Publisher {
	return &Publisher{
		config: cfg,
	}
}

/*
Publish šalje jednu outbox poruku na RabbitMQ.

Payload je već canonical envelope:
event_id, event_name, event_version, occurred_at, producer, payload.
*/
func (p *Publisher) Publish(ctx context.Context, message outboxdto.OutboxMessageData) error {
	connection, err := amqp.Dial(p.dsn())
	if err != nil {
		return fmt.Errorf("neuspešno povezivanje na RabbitMQ: %w", err)
	}
	defer connection.Close()

	channel, err := connection.Channel()
	if err != nil {
		return fmt.Errorf("neuspešno otvaranje RabbitMQ kanala: %w", err)
	}
	defer channel.Close()

	if err := channel.ExchangeDeclare(
		p.config.RabbitMQ.Exchange,
		p.config.RabbitMQ.ExchangeType,
		true,
		false,
		false,
		false,
		nil,
	); err != nil {
		return fmt.Errorf("neuspešno deklarisanje RabbitMQ exchange-a: %w", err)
	}

	body, err := json.Marshal(message.Payload)
	if err != nil {
		return fmt.Errorf("neuspešno JSON enkodovanje RabbitMQ poruke: %w", err)
	}

	headers := p.buildHeaders(message)

	for key, value := range message.Headers {
		headers[key] = value
	}

	if err := channel.PublishWithContext(
		ctx,
		p.config.RabbitMQ.Exchange,
		message.RoutingKey,
		false,
		false,
		amqp.Publishing{
			ContentType:  "application/json",
			DeliveryMode: amqp.Persistent,
			MessageId:    message.EventID,
			Type:         message.EventName,
			Headers:      headers,
			Body:         body,
		},
	); err != nil {
		return fmt.Errorf("neuspešno publishovanje RabbitMQ poruke: %w", err)
	}

	return nil
}

/*
dsn pravi RabbitMQ connection string.
*/
func (p *Publisher) dsn() string {
	return fmt.Sprintf(
		"amqp://%s:%s@%s:%d%s",
		p.config.RabbitMQ.User,
		p.config.RabbitMQ.Password,
		p.config.RabbitMQ.Host,
		p.config.RabbitMQ.Port,
		p.config.RabbitMQ.VHost,
	)
}

func (p *Publisher) buildHeaders(message outboxdto.OutboxMessageData) amqp.Table {
	return amqp.Table{
		"x-event-id":      message.EventID,
		"x-event-name":    message.EventName,
		"x-event-version": int32(message.EventVersion),
		"x-producer":      p.config.App.ServiceName,
	}
}
