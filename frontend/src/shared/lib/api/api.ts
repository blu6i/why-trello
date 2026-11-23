import { ApiError } from './ApiError'
import type { ApiFailure, ApiSuccess } from './types'

type RequestOptions = {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  body?: unknown
}

type ApiEnvelopeLike = {
  is_successful?: boolean
}

function getCsrfToken(): string | null {
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)
  return match ? decodeURIComponent(match[1]) : null
}

/**
 * Единственное место, где мы общаемся с бэкендом.
 * Успех/неуспех определяется ТОЛЬКО по полю is_successful.
 */
export async function request<T>(
  path: string,
  options: RequestOptions = {},
): Promise<ApiSuccess<T>> {
  const method = options.method ?? 'GET'
  const headers: Record<string, string> = { 'Content-Type': 'application/json' }

  if (method !== 'GET') {
    const csrfToken = getCsrfToken()
    if (csrfToken) {
      headers['X-XSRF-TOKEN'] = csrfToken
    }
  }

  const response = await fetch(path, {
    method,
    headers,
    // Куку сессии браузер отправит сам — JS её не читает
    credentials: 'include',
    body: options.body === undefined ? undefined : JSON.stringify(options.body),
  })

  let data: unknown
  try {
    data = await response.json()
  } catch {
    // Сервер ответил не JSON'ом (например, упал nginx или PHP)
    throw new ApiError({
      is_successful: false,
      time: new Date().toISOString(),
      error: 'Сервер вернул некорректный ответ',
    })
  }

  if (!(data as ApiEnvelopeLike).is_successful) {
    throw new ApiError(data as ApiFailure)
  }

  return data as ApiSuccess<T>
}
