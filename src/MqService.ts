import amqp, { AmqpConnectionManager, ChannelWrapper } from 'amqp-connection-manager';
import { Channel, ConsumeMessage } from 'amqplib';
import { EventsMap } from './TypeHelper';
import { MqError, MqHandler } from './MqTypes';
import { Events } from './EventMap';

/**
 * A message queue service for publishing and subscribing to messages.
 * @category Service
 */
export class MqService {
  private connection: AmqpConnectionManager | null = null;
  private channelWrapper: ChannelWrapper;
  private eventHandlers: Map<string, MqHandler<any>> = new Map();
  private consuming = false;
  private unregisteredHandlers = new Set<string>();

  /**
   * Create a new MqService instance.
   * @param connectionString The connection string to the message queue.
   * @param queueName The name of the queue to use. Normally this should be your service name. Only needed if you want to consume messages.
   * @param exchange The exchange to use.
   * @param requeueUnhandled If true, unhandled messages will be requeued, otherwise they will be discarded.
   * @param logger The logger to use for logging messages.
   */
  constructor(
    readonly connectionString: string = 'amqp://localhost',
    private readonly queueName: string|null = null,
    readonly exchange: string = 'general',
    private readonly requeueUnhandled: boolean = false,
    readonly logger: {
      log: (message: string) => void;
      error: (message: string, error: any) => void;
      warn: (message: string) => void;
    } = console,
  ) {
    if (connectionString.trim() === '') throw new Error("Connection string cant be empty");
    this.connection = amqp.connect([connectionString]);
    this.channelWrapper = this.connection.createChannel({
      setup: (channel: Channel) => {
        const setups: Promise<any>[] = [];
        setups.push(channel.checkExchange(this.exchange));
        if (queueName) setups.push(
          channel.assertQueue(queueName, { 
            durable: true,
            exclusive: false,
            autoDelete: false 
          }),
        );
        return Promise.all(setups);
      },
    });
  }

  async publish<TChannel extends keyof EventsMap>(
    event: TChannel,
    payload: EventsMap[TChannel]['payload'],
  ): Promise<void> {
    if (!this.connection) throw new Error('Connection closed');
    try {
      await this.channelWrapper.publish(
        this.exchange,
        Events[event].name,
        Buffer.from(JSON.stringify(payload)),
        {
          persistent: true,
          contentType: 'application/json',
          timestamp: Math.floor(Date.now() / 1000),
          headers: {
            event_name: Events[event].name,
          }
        }
      );
    } catch (error) {
      this.logger.error(`Failed to publish message for event ${String(event)}`, error);
      throw new Error(`Failed to publish message: ${error instanceof Error ? error.message : String(error)}`);
    }
  }

  getListenerCount(): number {
    return this.eventHandlers.size;
  }

  async subscribe<TChannel extends keyof EventsMap>(
    event: TChannel,
    handler: MqHandler<TChannel>,
  ): Promise<void> {
    const eventName = Events[event].name;
    if (this.eventHandlers.has(eventName)) throw new Error(`Handler already registered for event: ${eventName}`);
    this.eventHandlers.set(eventName, handler);
  }

  private async handleMessage(message: ConsumeMessage): Promise<void> {
    try {
      const headers = message.properties.headers || {};
      const eventName = headers.event_name;

      if (!eventName || !this.eventHandlers.has(eventName)) {
        if (!this.unregisteredHandlers.has(eventName)) {
          this.unregisteredHandlers.add(eventName);
          this.logger.warn(`No handler found for event ${eventName}`);
        }
        this.channelWrapper.nack(message, false, this.requeueUnhandled);
        return;
      }

      const handler = this.eventHandlers.get(eventName)!;
      const payload = JSON.parse(message.content.toString());

      try {
        const result = await Promise.resolve(handler(payload));
        if (result instanceof MqError) {
          this.channelWrapper.nack(message, false, result.requeue);
        } else if (result === false) {
          this.channelWrapper.nack(message, false, true);
        } else {
          this.channelWrapper.ack(message);
        }
      } catch (error) {
        this.logger.error(`Error processing message for event ${eventName}`, error);
        this.channelWrapper.nack(message, false, true);
      }
    } catch (error) {
      this.logger.error('Failed to handle message', error);
      this.channelWrapper.nack(message, false, true);
      throw error;
    }
  }

  async startConsuming(): Promise<void> {
    if (this.consuming) return;
    if (!this.connection) throw new Error('Connection closed');
    if (!this.queueName) throw new Error('Queue name not set');
    if (this.eventHandlers.size === 0) throw new Error('No event handlers registered');

    try {
      await this.channelWrapper.addSetup(async (channel: Channel) => {
        await channel.prefetch(1, false);
        this.logger.log('Starting to consume messages');
        await channel.consume(
          this.queueName!,
          async (message) => {
            if (!message) {
              this.logger.warn('Received null message');
              return;
            }
            await this.handleMessage(message);
          },
          {
            noAck: false
          }
        );
      });
    } catch (error) {
      this.logger.error('Failed to start consuming messages', error);
      throw new Error(`Failed to start consuming messages: ${error instanceof Error ? error.message : String(error)}`);
    }
  }

  async close(): Promise<void> {
    if (!this.connection) return;
    await this.channelWrapper.close();
    await this.connection.close();
    this.connection = null;
    this.consuming = false;
    this.eventHandlers.clear();
    this.unregisteredHandlers.clear();
  }
}
