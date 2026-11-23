import type { ApiFailure } from './types'

/**
 * Ошибка API. Бэкенд всегда отвечает конвертом с is_successful,
 * поэтому любая неуспешная операция — это ApiError.
 */
export class ApiError extends Error {
  /** Ошибки валидации по полям — их показываем прямо под input'ами. */
  readonly parameterErrors: Record<string, string[]>

  constructor(failure: ApiFailure) {
    super(failure.error)
    this.name = 'ApiError'
    this.parameterErrors = failure.parameter_errors ?? {}
  }
}
