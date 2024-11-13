import { MqService } from '../../dist/index.mjs'

(async () => {
  const mqService = new MqService()
  console.log('Sending message')
  await mqService.publish("CUSTOMER_MERGE", {
    newCustomerId: '123',
    oldCustomerId: '456',
    salesChannel: '789',
    test: {foo: 'bar'}
  });
  await mqService.close();
})().then();
