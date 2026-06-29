import { avecCache } from './cache'

const API_URL = import.meta.env.VITE_API_URL || ''

export class ApiError extends Error {
  status: number

  constructor(message: string, status: number) {
    super(message)
    this.name = 'ApiError'
    this.status = status
  }
}

function getToken(): string | null {
  return localStorage.getItem('novashop_token')
}

export async function api<T>(path: string, options: RequestInit = {}): Promise<T> {
  const token = getToken()
  const isFormData = options.body instanceof FormData

  const headers: HeadersInit = {
    ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  }

  let response: Response
  try {
    response = await fetch(`${API_URL}${path}`, { ...options, headers })
  } catch {
    throw new ApiError(
      'Impossible de joindre l\'API. Démarrez le backend (dossier Backend) : php -S localhost:8000 -t public',
      0,
    )
  }

  if (!response.ok) {
    const payload = await response.json().catch(() => ({ erreur: 'Erreur réseau' }))
    const message = payload.erreur || payload.message || 'Une erreur est survenue'
    if (message === 'Failed to fetch' || message === 'Erreur réseau') {
      throw new ApiError('Impossible de joindre l\'API. Démarrez le backend sur le port 8000.', response.status)
    }
    throw new ApiError(message, response.status)
  }

  if (response.status === 204) {
    return undefined as T
  }

  return response.json() as Promise<T>
}

export async function apiGet<T>(path: string, signal?: AbortSignal): Promise<T> {
  return api<T>(path, { method: 'GET', signal })
}

export function apiGetCached<T>(path: string, dureeMs: number, signal?: AbortSignal): Promise<T> {
  const cle = `GET:${path}`
  return avecCache(cle, () => apiGet<T>(path, signal), dureeMs)
}

export function imageUrl(path?: string | null, produitId = 1): string {
  const externeInaccessible = !path || path.includes('picsum.photos')

  if (!externeInaccessible && path.startsWith('http')) {
    return path
  }

  if (!externeInaccessible && path.startsWith('/images/')) {
    return path
  }

  if (!externeInaccessible && path.startsWith('/')) {
    return `${API_URL}${path}`
  }

  const hue = (produitId * 47) % 360
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" viewBox="0 0 600 600">
    <defs>
      <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" style="stop-color:hsl(${hue},55%,42%)"/>
        <stop offset="100%" style="stop-color:hsl(${(hue + 40) % 360},60%,28%)"/>
      </linearGradient>
    </defs>
    <rect width="600" height="600" fill="url(#g)"/>
    <circle cx="300" cy="240" r="80" fill="rgba(255,255,255,0.12)"/>
    <rect x="180" y="360" width="240" height="16" rx="8" fill="rgba(255,255,255,0.2)"/>
    <rect x="220" y="390" width="160" height="12" rx="6" fill="rgba(255,255,255,0.12)"/>
  </svg>`

  return `data:image/svg+xml,${encodeURIComponent(svg)}`
}

export { API_URL }
