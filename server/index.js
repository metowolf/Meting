import http from 'node:http';
import Meting from '../lib/meting.esm.js';

const PORT = Number(process.env.PORT || 3000);

/**
 * Minimal CORS helper (no external deps)
 */
function setCors(res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET,OPTIONS');
  res.setHeader(
    'Access-Control-Allow-Headers',
    'Content-Type, Authorization, X-Requested-With'
  );
}

function sendJsonString(res, jsonString) {
  res.statusCode = 200;
  res.setHeader('Content-Type', 'application/json; charset=utf-8');
  res.end(jsonString);
}

function sendError(res, statusCode, payload) {
  res.statusCode = statusCode;
  res.setHeader('Content-Type', 'application/json; charset=utf-8');
  res.end(JSON.stringify(payload));
}

function pick(obj, keys) {
  const out = {};
  for (const k of keys) {
    if (obj[k] !== undefined) out[k] = obj[k];
  }
  return out;
}

/**
 * Query format compatible with meting-api style:
 *   /?server=netease&type=playlist&id=8724039279
 * Also supports:
 *   /api?server=netease&type=search&keyword=xxx&limit=10&page=1
 *
 * Supported type:
 * - search (keyword required; supports page, limit, type(category))
 * - song, album, artist, playlist (id required; artist supports limit)
 * - url (id required; br optional)
 * - lyric (id required)
 * - pic (id required; size optional)
 */
async function handleMeting(query) {
  const server = query.server || 'netease';
  const type = (query.type || '').toString().toLowerCase();

  const meting = new Meting(server);
  meting.format(query.format === 'true' || query.format === '1' || query.format === true);

  // Normalize some common aliases
  const t =
    type === 'songs' ? 'song'
    : type === 'artists' ? 'artist'
    : type === 'albums' ? 'album'
    : type;

  if (!t) {
    throw new Error('Missing query param: type');
  }

  if (t === 'search') {
    const keyword = query.keyword ?? query.s;
    if (!keyword) throw new Error('Missing query param: keyword');
    const option = {
      ...pick(query, ['type', 'page', 'limit']),
    };
    // cast numeric
    if (option.type !== undefined) option.type = Number(option.type);
    if (option.page !== undefined) option.page = Number(option.page);
    if (option.limit !== undefined) option.limit = Number(option.limit);

    return await meting.search(String(keyword), option);
  }

  if (t === 'song') {
    const id = query.id;
    if (!id) throw new Error('Missing query param: id');
    return await meting.song(id);
  }

  if (t === 'album') {
    const id = query.id;
    if (!id) throw new Error('Missing query param: id');
    return await meting.album(id);
  }

  if (t === 'artist') {
    const id = query.id;
    if (!id) throw new Error('Missing query param: id');
    const limit = query.limit !== undefined ? Number(query.limit) : undefined;
    return await meting.artist(id, limit);
  }

  if (t === 'playlist') {
    const id = query.id;
    if (!id) throw new Error('Missing query param: id');
    return await meting.playlist(id);
  }

  if (t === 'url') {
    const id = query.id;
    if (!id) throw new Error('Missing query param: id');
    const br = query.br !== undefined ? Number(query.br) : undefined;
    return await meting.url(id, br);
  }

  if (t === 'lyric') {
    const id = query.id;
    if (!id) throw new Error('Missing query param: id');
    return await meting.lyric(id);
  }

  if (t === 'pic') {
    const id = query.id;
    if (!id) throw new Error('Missing query param: id');
    const size = query.size !== undefined ? Number(query.size) : undefined;
    return await meting.pic(id, size);
  }

  throw new Error(`Unsupported type: ${t}`);
}

const server = http.createServer(async (req, res) => {
  setCors(res);

  if (req.method === 'OPTIONS') {
    res.statusCode = 204;
    res.end();
    return;
  }

  try {
    const url = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`);

    // Only support GET for now
    if (req.method !== 'GET') {
      sendError(res, 405, { error: 'Method Not Allowed' });
      return;
    }

    // Health check
    if (url.pathname === '/health') {
      res.statusCode = 200;
      res.setHeader('Content-Type', 'application/json; charset=utf-8');
      res.end(JSON.stringify({ ok: true }));
      return;
    }

    // Accept both "/" and "/api"
    if (url.pathname !== '/' && url.pathname !== '/api') {
      sendError(res, 404, { error: 'Not Found' });
      return;
    }

    const query = Object.fromEntries(url.searchParams.entries());
    const result = await handleMeting(query);
    sendJsonString(res, result);
  } catch (e) {
    sendError(res, 500, {
      error: 'Meting API Error',
      message: e?.message || String(e),
    });
  }
});

server.listen(PORT, '0.0.0.0', () => {
  // eslint-disable-next-line no-console
  console.log(`[meting-api] listening on http://0.0.0.0:${PORT}`);
  console.log(`[meting-api] examples:`);
  console.log(`  - http://localhost:${PORT}/?server=netease&type=playlist&id=8724039279`);
  console.log(`  - http://localhost:${PORT}/api?server=netease&type=search&keyword=%E5%91%A8%E6%9D%B0%E4%BC%A6&limit=5`);
});
