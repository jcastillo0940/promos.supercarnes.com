import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '/api'

export interface ApiDebugEntry {
  id: number
  phase: 'request' | 'response' | 'error'
  method: string
  url: string
  status?: number
  durationMs?: number
  payload?: unknown
  error?: unknown
  timestamp: string
}

let debugSequence = 0
const requestStartedAt = new WeakMap<object, number>()

function redact(value: unknown, key = ''): unknown {
  if (value === null || value === undefined || typeof value === 'number' || typeof value === 'boolean') return value
  if (typeof value === 'string') {
    if (/authorization|token|password|qr_raw_text|cufe/i.test(key)) {
      return `[redacted string length=${value.length} tail=${value.slice(-12)}]`
    }
    return value.length > 2000 ? `${value.slice(0, 2000)}… [truncated]` : value
  }
  if (Array.isArray(value)) return value.map((item) => redact(item, key))
  if (typeof value === 'object') {
    return Object.fromEntries(Object.entries(value as Record<string, unknown>).map(([childKey, childValue]) => [childKey, redact(childValue, childKey)]))
  }
  return String(value)
}

function publishDebug(entry: Omit<ApiDebugEntry, 'id' | 'timestamp'>) {
  const fullEntry: ApiDebugEntry = {
    ...entry,
    id: ++debugSequence,
    timestamp: new Date().toISOString(),
  }
  const label = `[SuperCarnes API] ${fullEntry.phase.toUpperCase()} ${fullEntry.method} ${fullEntry.url}`
  if (fullEntry.phase === 'error') console.error(label, fullEntry)
  else console.info(label, fullEntry)
  window.dispatchEvent(new CustomEvent<ApiDebugEntry>('supercarnes:api-debug', { detail: fullEntry }))
}

export const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  requestStartedAt.set(config, performance.now())
  publishDebug({
    phase: 'request',
    method: (config.method ?? 'GET').toUpperCase(),
    url: `${config.baseURL ?? ''}${config.url ?? ''}`,
    payload: redact(config.data),
  })
  return config
})

api.interceptors.response.use(
  (response) => {
    publishDebug({
      phase: 'response',
      method: (response.config.method ?? 'GET').toUpperCase(),
      url: `${response.config.baseURL ?? ''}${response.config.url ?? ''}`,
      status: response.status,
      durationMs: Math.round(performance.now() - (requestStartedAt.get(response.config) ?? performance.now())),
      payload: redact(response.data),
    })
    return response
  },
  (error) => {
    const config = error?.config
    publishDebug({
      phase: 'error',
      method: (config?.method ?? 'GET').toUpperCase(),
      url: `${config?.baseURL ?? ''}${config?.url ?? ''}`,
      status: error?.response?.status,
      durationMs: config ? Math.round(performance.now() - (requestStartedAt.get(config) ?? performance.now())) : undefined,
      error: redact(error?.response?.data ?? error?.message ?? error),
    })
    return Promise.reject(error)
  },
)

export const setApiToken = (token: string | null) => {
  if (token) {
    api.defaults.headers.common.Authorization = `Bearer ${token}`
  } else {
    delete api.defaults.headers.common.Authorization
  }
}
