import { ApiError } from './ApiError'
import type { ApiFailure, ApiSuccess } from './types'

type RequestOptions = {
  method?: 'GET' | 'POST'
  body?: unknown
}

type ApiEnvelopeLike = {
  is_successful?: boolean
}

/**
 * Единственное место, где мы общаемся с бэкендом.
 * Успех/неуспех определяется ТОЛЬКО по полю is_successful.
 */
export async function request<T>(
  path: string,
  options: RequestOptions = {},
): Promise<ApiSuccess<T>> {
  const response = await fetch(path, {
    method: options.method ?? 'GET',
    headers: { 'Content-Type': 'application/json' },
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
