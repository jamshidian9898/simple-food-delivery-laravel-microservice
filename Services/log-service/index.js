const amqp = require('amqplib');
const { Client } = require('pg');

const RABBIT = {
  host: process.env.RABBITMQ_HOST || 'rabbitmq',
  port: process.env.RABBITMQ_PORT || 5672,
  user: process.env.RABBITMQ_USER || 'admin',
  pass: process.env.RABBITMQ_PASSWORD || 'password',
  exchange: 'logs',
  exchangeType: 'topic'
};

const PG = {
  host: process.env.PG_HOST || 'log-db',
  port: process.env.PG_PORT || 5432,
  user: process.env.PG_USER || 'log-db',
  password: process.env.PG_PASSWORD || 'secret',
  database: process.env.PG_DATABASE || 'log-db'
};

/**
 * Repeatedly invokes `fn` with exponential backoff delays until it resolves or the attempt limit is reached.
 *
 * @param {Function} fn - Function that returns a value or a promise; invoked on each attempt.
 * @param {number} [maxRetries=10] - Maximum number of attempts before giving up.
 * @param {number} [baseDelay=1000] - Initial delay in milliseconds used to compute backoff (delay = baseDelay * 2^(attemptIndex)).
 * @returns {*} The value returned by `fn` from a successful attempt.
 * @throws {*} The error from the final failed attempt when all retries are exhausted.
 */
async function retryWithBackoff(fn, maxRetries = 10, baseDelay = 1000) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await fn();
    } catch (error) {
      if (i === maxRetries - 1) {
        throw error;
      }
      const delay = baseDelay * Math.pow(2, i);
      console.log(`Attempt ${i + 1} failed, retrying in ${delay}ms...`);
      await new Promise(resolve => setTimeout(resolve, delay));
    }
  }
}

/**
 * Establishes and returns a connected PostgreSQL client.
 * @returns {Promise<Client>} The connected PostgreSQL `Client` instance.
 */
async function connectPostgres() {
  return retryWithBackoff(async () => {
    const pg = new Client(PG);
    await pg.connect();
    console.log('Connected to Postgres');
    return pg;
  }, 10, 1000);
}

/**
 * Establishes a RabbitMQ connection using configured RABBIT settings with retry and backoff.
 * @returns {import('amqplib').Connection} The established AMQP connection.
 */
async function connectRabbitMQ() {
  return retryWithBackoff(async () => {
    const conn = await amqp.connect({
      protocol: 'amqp',
      hostname: RABBIT.host,
      port: RABBIT.port,
      username: RABBIT.user,
      password: RABBIT.pass
    });
    console.log('Connected to RabbitMQ');
    return conn;
  }, 10, 2000);
}

/**
 * Start the logging service: ensure storage exists, connect to RabbitMQ and Postgres (with retries), and begin consuming log messages.
 *
 * Sets up the `logs` table if missing, declares a durable topic exchange and a durable queue bound to all routing keys, and consumes messages from that queue. Each consumed JSON message is parsed and persisted into the `logs` table; messages are acknowledged on successful insert and negatively acknowledged without requeue on processing errors.
 */
async function start() {
  // connect Postgres with retry
  const pg = await connectPostgres();

  // ensure table exists
  await pg.query(`
    CREATE TABLE IF NOT EXISTS logs (
      id BIGSERIAL PRIMARY KEY,
      request_id UUID NULL,
      service_name VARCHAR(100) NULL,
      level VARCHAR(20) NULL,
      event_name VARCHAR(100) NULL,
      message TEXT NULL,
      data JSONB NULL,
      created_at TIMESTAMP WITH TIME ZONE DEFAULT now()
    );
  `);

  // connect RabbitMQ with retry
  const conn = await connectRabbitMQ();
  const ch = await conn.createChannel();
  await ch.assertExchange(RABBIT.exchange, RABBIT.exchangeType, { durable: true });

  // create a dedicated queue for log-service and bind to all routing keys for services
  const q = 'log-service-queue';
  await ch.assertQueue(q, { durable: true });
  // bind to everything under service.* (publisher uses keys like service.order.info)
  await ch.bindQueue(q, RABBIT.exchange, '#');

  console.log('Waiting for logs...');

  ch.consume(q, async (msg) => {
    if (!msg) return;
    try {
      const content = msg.content.toString();
      const payload = JSON.parse(content);
      // expected payload: { event, service, request_id, level, message, data, timestamp }
      const reqId = payload.request_id || null;
      const service = payload.service || null;
      const level = payload.level || null;
      const eventName = payload.event || null;
      const message = payload.message || payload.event || null;
      const data = payload.data || null;

      await pg.query(
        `INSERT INTO logs (request_id, service_name, level, event_name, message, data)
         VALUES ($1,$2,$3,$4,$5,$6)`,
        [reqId, service, level, eventName, message, data ? JSON.stringify(data) : null]
      );

      ch.ack(msg);
    } catch (err) {
      console.error('Failed processing message', err);
      // برای محافظه‌کاری نکن ack کنیم تا دوباره ری-تری شود یا به DLQ بره
      // ch.nack(msg, false, true);
      ch.nack(msg, false, false); // برای جلوگیری از loop، این را تنظیم کن آگاهانه
    }
  }, { noAck: false });
}

start().catch(err => {
  console.error(err);
  process.exit(1);
});
