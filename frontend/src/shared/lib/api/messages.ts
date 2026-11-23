import type { ApiError } from './ApiError'

/**
 * Маппинг кодов ошибок валидации на человекочитаемые сообщения.
 * Коды ошибок берём из бэкенда.
 */
const parameterMessages: Record<string, string> = {
  required: 'Обязательное поле',
  not_nullable: 'Значение не может быть пустым',
  invalid_value: 'Некорректное значение',
  invalid_string: 'Некорректное значение',
  invalid_float: 'Некорректное число',
  invalid_integer: 'Некорректное целое число',
  invalid_boolean: 'Некорректное значение',
  invalid_datetime: 'Некорректная дата и время',
  invalid_datetime_format: 'Некорректный формат даты и времени',
  cannot_be_past: 'Дата не может быть в прошлом',
  invalid_email: 'Некорректный email',
  min_value: 'Значение слишком маленькое',
  max_value: 'Значение слишком большое',
  min_length: 'Слишком короткое значение',
  max_length: 'Слишком длинное значение',
  min_date: 'Дата слишком ранняя',
  max_date: 'Дата слишком поздняя',
}

/**
 * parameter_errors: { email: ['required', 'invalid_email'] } -> { email: 'Обязательное поле. Некорректный email' }
 */
export function formatParameterErrors(
  parameterErrors: Record<string, string[]>,
): Record<string, string> {
  const formatted: Record<string, string> = {}

  for (const [field, codes] of Object.entries(parameterErrors)) {
    formatted[field] = codes
      .map((code) => parameterMessages[code] ?? 'Проверьте значение')
      .join('. ')
  }

  return formatted
}

/**
 * Логические ошибки бэкенда (RequestApiException) -> текст для баннера.
 */
const requestMessages: Record<string, string> = {
  'User already exists': 'Пользователь с таким email уже зарегистрирован',
  'User authorization error': 'Неверный email или пароль',
  'Can`t authorize user': 'Неверный email или пароль',
  'Authorized user': 'Вы уже авторизованы',
  'Can`t authorize new user': 'Не удалось войти под новым пользователем',
  'User not found': 'Пользователь не найден',
}

export function formatRequestError(error: ApiError): string {
  return requestMessages[error.message] ?? 'Не удалось выполнить запрос. Попробуйте ещё раз'
}
