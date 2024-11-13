const { Events } = require('../dist');
const names = new Set();

let fail = false;
for (const key of Object.keys(Events)) {
  const event = Events[key];
  if (names.has(event.name)) {
    console.error(`Duplicate event name: ${event.name}`);
    fail = true;
  } else names.add(event.name);
}

if (fail) process.exit(1);
else console.log('All event names are unique!');
