import {
  Injectable,
  Logger,
  OnApplicationBootstrap,
  OnModuleDestroy,
} from '@nestjs/common';
import { MqService } from '../../../dist/index';

@Injectable()
export class MqAutostartService
  implements OnApplicationBootstrap, OnModuleDestroy
{
  private readonly logger = new Logger(MqAutostartService.name);
  constructor(private readonly mqService: MqService) {}

  onApplicationBootstrap() {
    if (this.mqService.getListenerCount() > 0) this.mqService.startConsuming();
    else
      this.logger.log('No event listener registered, not starting consuming');
  }

  onModuleDestroy() {
    this.mqService.close();
  }
}
