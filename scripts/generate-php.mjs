import { PHP_DEFAULT_PRESET, PHP_DESCRIPTION_PRESET, PHP_JSON_SERIALIZABLE_PRESET, PhpGenerator } from '@asyncapi/modelina';
import { existsSync, readFileSync, writeFileSync, rmSync, mkdirSync } from 'fs';

if (!existsSync('./dist/schema.json')) {
  console.error('Schema file not found in dist/schema.json');
  process.exit(1);
}

const OUTPUT_DIR = './php';
const MODELS_OUTPUT_DIR = OUTPUT_DIR + '/Models';
const LISTENER_OUTPUT_DIR = OUTPUT_DIR + '/Listeners';

if (!existsSync(OUTPUT_DIR)) mkdirSync(OUTPUT_DIR);
if (existsSync(MODELS_OUTPUT_DIR)) rmSync(MODELS_OUTPUT_DIR, { recursive: true, force: true });
if (existsSync(LISTENER_OUTPUT_DIR)) rmSync(LISTENER_OUTPUT_DIR, { recursive: true, force: true });
if (existsSync(OUTPUT_DIR + '/MqService.php')) rmSync(OUTPUT_DIR + '/MqService.php');
mkdirSync(MODELS_OUTPUT_DIR);
mkdirSync(LISTENER_OUTPUT_DIR);

const schema = JSON.parse(readFileSync('./dist/schema.json', 'utf-8'));

const generator = new PhpGenerator({
  presets: [
    PHP_JSON_SERIALIZABLE_PRESET,
    PHP_DESCRIPTION_PRESET,
  ],
  defaultPreset: {
    class: {
      ...PHP_DEFAULT_PRESET.class,
      property: () => {},
      ctor: (o) => {
        const sorted = Object.entries(o.model.properties).sort((a, b) => {
          if (a[1].required && !b[1].required) return -1;
          if (!a[1].required && b[1].required) return 1;
          return 0;
        });

        const docs = sorted.map(([_, prop]) => {
          const desc = prop.property.originalInput?.description
          return [
            '@param ' + prop.property.type + ' $' + prop.propertyName,
            desc ? ' ' + desc : '',
          ].join('');
        }).join('\n * ');

        const properties = sorted.map(([_, prop]) => {
          return [
            `private ${prop.required ? '' : '?'}${prop.property.type}`,
            `${prop.property.options.isNullable ? '|null' : ''} $${prop.propertyName},`
          ].join('');
        }).join('\n  ');

        const setter = sorted.map(([_, prop]) => {
          if (!prop.property.ref) return `$data['${prop.propertyName}'] ?? null,`;
          return `isset($data['${prop.propertyName}']) ? ${prop.property.type}::from_json($data['${prop.propertyName}']) : null,`;
        }).join('\n    ');
  
        return [
          '/**',
          ` * ${o.model.originalInput?.description || `This class represents the ${o.modelName} model.`}`,
          ' * ',
          ` * ${docs}`,
          ' * @return self',
          ' */',
          'public function __construct(',
          `  ${properties}`,
          ')',
          '{',
          '}',
          '',
          '/**',
          ' * Parse JSON data into an instance of this class.',
          ' * ',
          ' * @param string|array $json JSON data to parse.',
          ' * @return self',
          ' */',
          'public static function from_json(string|array $json): self',
          '{',
          '  $data = is_string($json) ? json_decode($json, true) : $json;',
          '  return new self(',
          `    ${setter}`,
          '  );',
          '}',
        ].join('\n');
      },
    },
    enum: PHP_DEFAULT_PRESET.enum,
  }
});

export async function generate() {
  for (const key of Object.keys(schema.definitions)) {
    if (key === 'EventsMap') continue;
    console.log('Generating model for:', key);
    const models = await generator.generateCompleteModels({
      "$schema": schema["$schema"],
      ...schema.definitions[key],
      title: key,
      definitions: schema.definitions,
    }, { 
      namespace: 'Tecsafe\\OFCP\\Events\\Models',
    });
    for (const m of models) {
      writeFileSync(`${MODELS_OUTPUT_DIR}/${m.modelName}.php`, String(m.result));
    }
  }

  const typeMap = Object.entries(schema.definitions.EventsMap.properties).reduce((acc, [key, val]) => {
    acc[key] = val.const;
    return acc;
  }, {})

  const deDupedValues = [...new Set(Object.values(typeMap))];

  for (const val of deDupedValues) {
    const listenerPhp = `<?php
declare(strict_types=1);

namespace Tecsafe\\OFCP\\Events\\Listeners;

use Tecsafe\\OFCP\\Events\\Models\\${val};
use Tecsafe\\OFCP\\Events\\MqServiceError;

interface ${val}Listener {
  /**
   * Handle the ${val} event. If returning false, the event will be requeued.
   * @param ${val} $event The event to handle.
   * @return bool|\\Tecsafe\\OFCP\\Events\\MqServiceError True if the event was handled successfully, or an error object / false if not
   */
  public function on_event(${val} $event): bool | MqServiceError;
}
`;
    writeFileSync(`${LISTENER_OUTPUT_DIR}/${val}Listener.php`, listenerPhp);
  }

  const servicePhp = `<?php
declare(strict_types=1);

namespace Tecsafe\\OFCP\\Events;

${deDupedValues.map((f) => `use Tecsafe\\OFCP\\Events\\Models\\${f};`).join('\n')}
${deDupedValues.map((f) => `use Tecsafe\\OFCP\\Events\\Listeners\\${f}Listener;`).join('\n')}
use Tecsafe\\OFCP\\Events\\MqServiceBase;

class MqService extends MqServiceBase
{

${Object.entries(typeMap).map(([key, val]) => {
  const sanitized = key.replaceAll('.', '_');
  return `  /**
   * Send the ${key} event.
   * @param ${val} $payload The payload to send.
   * @return void
   * @throws \\Exception If the message could not be sent.
   */
  public function send_${sanitized}(${val} $payload): void
  {
    $this->publish("${key}", json_encode($payload));
  }

  /**
   * Subscribe to the ${key} event.
   * @param callable|${val}Listener $callback The callback or listener instance to call when the event is received.
   */
  public function subscribe_${sanitized}(callable|${val}Listener $callback): void
  {
    $handler = function (string $payload) use ($callback) {
      $obj = ${val}::from_json($payload);
      $res = false;
      if ($callback instanceof ${val}Listener) {
        $res = $callback->on_event($obj);
      } else {
        $res = $callback($obj);
      }
      return $res;
    };
    $this->subscribe("${key}", $handler);
  }`
}).join('\n\n')}

}
`;
  writeFileSync(`${OUTPUT_DIR}/MqService.php`, servicePhp);
}

generate().then(() => {
  console.log('PHP code generated successfully!');
});
