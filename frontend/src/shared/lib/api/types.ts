/**
 * Общий формат любого ответа от бэкенда
 */
export type ApiEnvelope = {
  is_successful: boolean
  time: string
}

/**
 * Неуспешный ответ: HTTP 4xx/5xx, is_successful = false.
 */
export type ApiFailure = ApiEnvelope & {
  is_successful: false
  /** Общий текст ошибки от бэкенда (на английском — переводим в messages.ts). */
  error: string
  /** Ошибки валидации по полям: { email: ['required', 'invalid_email'] }. */
  parameter_errors?: Record<string, string[]>
}

/**
 * Успешный ответ: поля модели лежат «плоско» рядом с другими полями.
 */
export type ApiSuccess<T> = ApiEnvelope & { is_successful: true } & T
