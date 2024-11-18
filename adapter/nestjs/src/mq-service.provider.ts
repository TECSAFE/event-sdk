import { Provider } from '@nestjs/common';
import { MqService } from '../../../dist/index';

export class MqServiceProvider {
  static create(...args: ConstructorParameters<typeof MqService>): Provider {
    return {
      provide: MqService,
      useFactory: () => {
        return new MqService(...args);
      },
    };
  }
}
