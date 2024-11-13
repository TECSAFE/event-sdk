import { MqService, MqError } from '../../dist/index.mjs'

(async () => {
  const mqService = new MqService('amqp://localhost', 'test')
  await mqService.subscribe("CUSTOMER_MERGE", (msg) => {
    console.log('merge:', msg)
    return true;
  });
  await mqService.subscribe("CUSTOMER_DELETE", (msg) => {
    console.log('delete:', msg)
    return new MqError(false);
  });
  await mqService.startConsuming();
})().then();
