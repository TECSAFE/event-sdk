import * as fs from 'fs';
import * as path from 'path';
import ts from 'typescript';

function unwrapExpression(expression, unwrapAsExpression = true) {
  while (
    (unwrapAsExpression && ts.isAsExpression(expression)) ||
    ts.isParenthesizedExpression(expression) ||
    ts.isTypeAssertionExpression(expression) ||
    ts.isNonNullExpression(expression)
  ) {
    expression = expression.expression;
  }
  return expression;
}

function findEventsObject(sourceFile) {
  let eventsObject = null;
  ts.forEachChild(sourceFile, node => {
    if (!ts.isVariableStatement(node)) return;
    const declaration = node.declarationList.declarations[0];
    if (
      !ts.isIdentifier(declaration.name) ||
      declaration.name.escapedText !== 'Events'
    )
      return;
    eventsObject = declaration.initializer;
  });
  return eventsObject;
}

function collectProperties(expression, program) {
  const properties = [];

  // Unwrap AsExpression at the top level
  expression = unwrapExpression(expression, true);

  if (ts.isObjectLiteralExpression(expression)) {
    for (const prop of expression.properties) {
      if (ts.isSpreadAssignment(prop)) {
        const spreadExpression = unwrapExpression(prop.expression, true);

        if (ts.isIdentifier(spreadExpression)) {
          const typeChecker = program.getTypeChecker();
          let symbol = typeChecker.getSymbolAtLocation(spreadExpression);

          if (symbol) {
            // Resolve the symbol if it's an alias (e.g., from an import)
            if ((symbol.flags & ts.SymbolFlags.Alias) !== 0) {
              symbol = typeChecker.getAliasedSymbol(symbol);
            }

            const declarations = symbol.getDeclarations();
            if (declarations && declarations.length > 0) {
              for (const decl of declarations) {
                if (ts.isVariableDeclaration(decl) && decl.initializer) {
                  const spreadProperties = collectProperties(
                    decl.initializer,
                    program
                  );
                  properties.push(...spreadProperties);
                }
              }
            }
          }
        }
      } else if (ts.isPropertyAssignment(prop)) {
        properties.push(prop);
      }
    }
  }

  return properties;
}

function generateEventMapping(filePath) {
  const program = ts.createProgram({
    rootNames: [filePath],
    options: {
      module: ts.ModuleKind.CommonJS,
      target: ts.ScriptTarget.ES2015,
      moduleResolution: ts.ModuleResolutionKind.NodeJs,
    },
  });
  const sourceFile = program.getSourceFile(filePath);

  if (!sourceFile) {
    throw new Error(`Could not find source file: ${filePath}`);
  }

  const eventsObject = findEventsObject(sourceFile);
  if (!eventsObject) {
    throw new Error('Could not find Events object in source file');
  }

  const eventMapping = {};

  const properties = collectProperties(eventsObject, program);

  for (const prop of properties) {
    let name, type;

    if (!ts.isPropertyAssignment(prop)) continue;

    const initializer = prop.initializer;

    if (!ts.isObjectLiteralExpression(initializer)) continue;

    const propProperties = initializer.properties;

    for (const val of propProperties) {
      if (!ts.isPropertyAssignment(val)) continue;

      const valName = val.name;

      if (ts.isIdentifier(valName) && valName.escapedText === 'name') {
        if (ts.isStringLiteral(val.initializer)) {
          name = val.initializer.text;
        }
      } else if (
        ts.isIdentifier(valName) &&
        valName.escapedText === 'payload'
      ) {
        const payloadInitializer = val.initializer; // Do not unwrap

        if (ts.isAsExpression(payloadInitializer)) {
          const typeNode = payloadInitializer.type;
          type = typeNode.getText();
        } else {
          // Handle other cases if needed
        }
      }
    }

    if (name && type) {
      eventMapping[name] = type;
    }
  }

  return eventMapping;
}

const filePath = path.resolve('./src/EventMap.ts');
const mapping = generateEventMapping(filePath);
const schema = JSON.parse(fs.readFileSync('./dist/schema.json', 'utf-8'));
schema.definitions.EventsMap = {
  type: 'object',
  properties: Object.keys(mapping).reduce((acc, val) => {
    acc[val] = {
      type: 'string',
      const: mapping[val],
    };
    return acc;
  }, {}),
  required: Object.keys(mapping),
  additionalProperties: false,
};
fs.writeFileSync('./dist/schema.json', JSON.stringify(schema, null, 2));
console.log('Successfully generated EventsMap schema');
