const { Events } = require('../dist');
const names = new Set();

let fail = false;
for (const key of Object.keys(Events)) {
  const event = Events[key];
  if (names.has(event.channel)) {
    console.error(`Duplicate channel: ${event.channel}`);
    fail = true;
  } else names.add(event.channel);
}

if (fail) process.exit(1);
else console.log('All channels are unique!');
