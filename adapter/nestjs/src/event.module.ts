import { DynamicModule, Global, Module } from '@nestjs/common';
import { MqServiceProvider } from './mq-service.provider';
import { MqAutostartService } from './mq-autostart.service';
import { type MqService } from '../../../dist/index';

/**
 * @category Adapter / NestJS
 */
@Global()
@Module({})
export class NestJsEventModule {
  static forRoot(
    ...args: ConstructorParameters<typeof MqService>
  ): DynamicModule {
    const mqServiceProvider = MqServiceProvider.create(...args);

    return {
      module: NestJsEventModule,
      providers: [mqServiceProvider, MqAutostartService],
      exports: [mqServiceProvider],
    };
  }
}
