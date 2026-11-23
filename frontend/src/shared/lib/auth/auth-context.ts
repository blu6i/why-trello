import { createContext } from 'react'
import type { User } from '../../types'

export type AuthContextValue = {
  /** Текущий пользователь или null, если гость. */
  user: User | null
  /** Пока идёт первоначальный запрос user — показываем «Загрузка…». */
  isLoading: boolean
  login: (email: string, password: string) => Promise<User>
  register: (name: string, email: string, password: string) => Promise<User>
  logout: () => Promise<void>
}

export const AuthContext = createContext<AuthContextValue | null>(null)
