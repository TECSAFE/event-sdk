import { PHP_DEFAULT_PRESET, PHP_DESCRIPTION_PRESET, PHP_JSON_SERIALIZABLE_PRESET, PhpGenerator } from '@asyncapi/modelina';
import { existsSync, readFileSync, writeFileSync, rmSync, mkdirSync } from 'fs';

if (!existsSync('./dist/schema.json')) {
  console.error('Schema file not found in dist/schema.json');
  process.exit(1);
}

if (existsSync('./php')) {
  rmSync('./php', { recursive: true, force: true });
}
mkdirSync('./php');

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
          if (a[1].required && !b[1].required) return 1;
          if (!a[1].required && b[1].required) return -1;
          return 0;
        });
        const properties = sorted.map(([_, prop]) => {
          return [
            `private ${prop.required ? '?' : ''}${prop.property.type}`,
            `${prop.property.options.isNullable ? '|null' : ''} $${prop.propertyName},`
          ].join('');
        }).join('\n  ');
  
        return [
          'public function __construct(',
          `  ${properties}`,
          ') {}'
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
      namespace: 'Tecsafe\\OFCP\\Events',
    });
    for (const m of models) {
      writeFileSync(`./php/${m.modelName}.php`, String(m.result));
    }
  }
}

generate();
