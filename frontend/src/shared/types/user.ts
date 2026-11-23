/**
 * Пользователь приложения — в том виде, в каком его отдаёт бэкенд
 * (см. UserResponseModel в app/src/Library/Api/Responses).
 */
export type User = {
  user_id: number
  email: string
  name: string
  created_datetime: string
  updated_datetime: string | null
}
