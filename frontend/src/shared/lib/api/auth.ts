import type { User } from '../../types'
import { request } from './api'
import type { ApiSuccess } from './types'

/**
 * Получаем пользователя из ответа API
 */
function pickUser(data: ApiSuccess<User>): User {
  return {
    user_id: data.user_id,
    email: data.email,
    name: data.name,
    created_datetime: data.created_datetime,
    updated_datetime: data.updated_datetime ?? null,
  }
}

/**
 * Текущий пользователь из PHP-сессии.
 * Для гостя бэкенд отвечает 400 (is_successful = false) — это нормальное состояние, а не ошибка.
 */
export async function getCurrentUser(): Promise<User | null> {
  try {
    return pickUser(await request<User>('/api/frontend/user'))
  } catch {
    return null
  }
}

export async function login(email: string, password: string): Promise<User> {
  return pickUser(
    await request<User>('/api/frontend/login', { method: 'POST', body: { email, password } }),
  )
}

export async function register(name: string, email: string, password: string): Promise<User> {
  return pickUser(
    await request<User>('/api/frontend/register', {
      method: 'POST',
      body: { name, email, password },
    }),
  )
}

export async function logout(): Promise<void> {
  await request('/api/frontend/logout')
}
